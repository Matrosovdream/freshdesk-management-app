<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Support\ManagerScope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ExportCompaniesAction
{
    public function handle(array $data = []): array
    {
        $q = Company::query();
        ManagerScope::applyToCompanies($q);

        $name = 'exports/companies-'.Str::uuid().'.csv';
        $disk = Storage::disk('public');
        $tmp = tmpfile();
        fputcsv($tmp, ['id', 'name', 'domains', 'industry', 'account_tier', 'health_score', 'renewal_date']);
        $q->cursor()->each(function ($c) use ($tmp) {
            fputcsv($tmp, [
                $c->id, $c->name, json_encode($c->domains ?? []),
                $c->industry, $c->account_tier, $c->health_score,
                optional($c->renewal_date)->toDateString(),
            ]);
        });
        fseek($tmp, 0);
        $disk->put($name, stream_get_contents($tmp));
        fclose($tmp);

        return ['download_url' => $disk->url($name)];
    }
}
