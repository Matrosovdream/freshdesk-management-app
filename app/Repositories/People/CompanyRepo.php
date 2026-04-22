<?php

namespace App\Repositories\People;

use App\Models\Company;
use App\Repositories\AbstractRepo;

class CompanyRepo extends AbstractRepo
{
    public function __construct()
    {
        $this->model = new Company();
    }

    public function getByFreshdeskId(int $fdId)
    {
        return $this->mapItem($this->model->where('freshdesk_id', $fdId)->first());
    }

    public function getByDomain(string $domain)
    {
        $item = $this->model
            ->whereJsonContains('domains', $domain)
            ->first();

        return $this->mapItem($item);
    }

    public function upsertFromFreshdesk(array $payload): array
    {
        $item = $this->model->updateOrCreate(
            ['freshdesk_id' => $payload['id']],
            [
                'name'          => $payload['name'] ?? null,
                'description'   => $payload['description'] ?? null,
                'domains'       => $payload['domains'] ?? null,
                'note'          => $payload['note'] ?? null,
                'health_score'  => $payload['health_score'] ?? null,
                'account_tier'  => $payload['account_tier'] ?? null,
                'renewal_date'  => $payload['renewal_date'] ?? null,
                'industry'      => $payload['industry'] ?? null,
                'custom_fields' => $payload['custom_fields'] ?? null,
                'payload'       => $payload,
                'fd_created_at' => $payload['created_at'] ?? null,
                'fd_updated_at' => $payload['updated_at'] ?? null,
                'synced_at'     => now(),
            ],
        );

        return $this->mapItem($item->fresh());
    }

    public function mapItem($item)
    {
        if (empty($item)) {
            return null;
        }

        return [
            'id'            => $item->id,
            'freshdesk_id'  => $item->freshdesk_id,
            'name'          => $item->name,
            'description'   => $item->description,
            'domains'       => $item->domains,
            'industry'      => $item->industry,
            'account_tier'  => $item->account_tier,
            'health_score'  => $item->health_score,
            'renewal_date'  => $item->renewal_date,
            'fd_updated_at' => $item->fd_updated_at,
            'Model'         => $item,
        ];
    }
}
