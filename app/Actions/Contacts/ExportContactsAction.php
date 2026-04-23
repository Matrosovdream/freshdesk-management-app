<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Support\ManagerScope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ExportContactsAction
{
    public function handle(array $data = []): array
    {
        $q = Contact::query();
        ManagerScope::applyToContacts($q);

        $filters = (array) ($data['filters'] ?? []);
        if (!empty($filters['company_id'])) $q->where('company_id', (int) $filters['company_id']);
        if (!empty($filters['search']))     $q->where('name', 'like', '%'.$filters['search'].'%');

        $name = 'exports/contacts-'.Str::uuid().'.csv';
        $disk = Storage::disk('public');
        $tmp = tmpfile();
        fputcsv($tmp, ['id', 'name', 'email', 'phone', 'company_id', 'tags', 'fd_updated_at']);
        $q->cursor()->each(function ($c) use ($tmp) {
            fputcsv($tmp, [
                $c->id, $c->name, $c->email, $c->phone, $c->company_id,
                json_encode($c->tags ?? []), optional($c->fd_updated_at)->toIso8601String(),
            ]);
        });
        fseek($tmp, 0);
        $disk->put($name, stream_get_contents($tmp));
        fclose($tmp);

        return ['download_url' => $disk->url($name)];
    }
}
