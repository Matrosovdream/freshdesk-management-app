<?php

namespace App\Repositories\System;

use App\Models\Setting;
use App\Repositories\AbstractRepo;
use Illuminate\Support\Facades\Crypt;

class SettingRepo extends AbstractRepo
{
    public function __construct()
    {
        $this->model = new Setting();
    }

    public function get(string $key, $default = null)
    {
        $row = $this->model->where('key', $key)->first();
        if (!$row) {
            return $default;
        }

        return $this->castValue($row->type, $row->value);
    }

    public function set(string $key, $value, string $type = 'string', string $group = 'general', ?string $description = null): array
    {
        $stored = $this->prepareValue($type, $value);

        $row = $this->model->updateOrCreate(
            ['key' => $key],
            [
                'value'       => $stored,
                'type'        => $type,
                'group'       => $group,
                'description' => $description,
            ],
        );

        return $this->mapItem($row->fresh());
    }

    public function getGroup(string $group): array
    {
        return $this->model
            ->where('group', $group)
            ->get()
            ->mapWithKeys(fn ($r) => [$r->key => $this->castValue($r->type, $r->value)])
            ->all();
    }

    protected function castValue(string $type, ?string $value)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int'       => (int) $value,
            'bool'      => (bool) (int) $value,
            'json'      => json_decode($value, true),
            'encrypted' => $value === '' ? '' : Crypt::decryptString($value),
            default     => $value,
        };
    }

    protected function prepareValue(string $type, $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int'       => (string) (int) $value,
            'bool'      => $value ? '1' : '0',
            'json'      => json_encode($value),
            'encrypted' => $value === '' ? '' : Crypt::encryptString((string) $value),
            default     => (string) $value,
        };
    }

    public function mapItem($item)
    {
        if (empty($item)) {
            return null;
        }

        return [
            'id'          => $item->id,
            'key'         => $item->key,
            'type'        => $item->type,
            'group'       => $item->group,
            'description' => $item->description,
            'Model'       => $item,
        ];
    }
}
