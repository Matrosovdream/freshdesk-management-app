<?php

namespace App\Actions\Config;

use App\Models\Setting;

final class ListTicketFieldsAction
{
    public function handle(array $data = []): array
    {
        $json = Setting::where('key', 'config.ticket_fields')->value('value');
        if (! $json) return $this->defaults();

        $decoded = json_decode($json, true);
        
        return is_array($decoded) ? $decoded : $this->defaults();
    }

    private function defaults(): array
    {
        return [
            ['name' => 'type',     'label' => 'Type',     'type' => 'select', 'choices' => ['Question', 'Incident', 'Problem', 'Feature Request', 'Refund']],
            ['name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'choices' => [['label' => 'Low', 'value' => 1], ['label' => 'Medium', 'value' => 2], ['label' => 'High', 'value' => 3], ['label' => 'Urgent', 'value' => 4]]],
        ];
    }
}
