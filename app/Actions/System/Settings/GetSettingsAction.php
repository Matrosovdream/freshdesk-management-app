<?php

namespace App\Actions\System\Settings;

use App\Models\Setting;

final class GetSettingsAction
{
    public function handle(array $data = []): array
    {
        return Setting::orderBy('group')->orderBy('key')->get()->map(function ($s) {
            $value = $s->value;
            if ($s->type === 'boolean') $value = (bool) ($value === '1' || $value === 'true');
            if ($s->type === 'integer') $value = (int) $value;
            if ($s->type === 'json')    $value = json_decode((string) $value, true);
            if ($s->type === 'encrypted') $value = ''; // never expose
            return [
                'key'         => $s->key,
                'value'       => $value,
                'type'        => $s->type,
                'group'       => $s->group,
                'description' => $s->description,
                'label'       => \Illuminate\Support\Str::of($s->key)->replace('.', ' ')->title()->value(),
            ];
        })->all();
    }
}
