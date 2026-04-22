<?php

namespace App\Repositories\People;

use App\Models\Contact;
use App\Repositories\AbstractRepo;

class ContactRepo extends AbstractRepo
{
    protected $withRelations = ['company'];

    public function __construct()
    {
        $this->model = new Contact();
    }

    public function getByFreshdeskId(int $fdId)
    {
        $item = $this->model
            ->where('freshdesk_id', $fdId)
            ->with($this->withRelations)
            ->first();

        return $this->mapItem($item);
    }

    public function getByEmail(string $email)
    {
        $item = $this->model
            ->where('email', $email)
            ->with($this->withRelations)
            ->first();

        return $this->mapItem($item);
    }

    public function getByCompany(int $companyId, $paginate = 50)
    {
        return $this->getAll(['company_id' => $companyId], $paginate);
    }

    public function upsertFromFreshdesk(array $payload): array
    {
        $item = $this->model->updateOrCreate(
            ['freshdesk_id' => $payload['id']],
            [
                'name'                  => $payload['name'] ?? null,
                'email'                 => $payload['email'] ?? null,
                'phone'                 => $payload['phone'] ?? null,
                'mobile'                => $payload['mobile'] ?? null,
                'twitter_id'            => $payload['twitter_id'] ?? null,
                'unique_external_id'    => $payload['unique_external_id'] ?? null,
                'freshdesk_company_id'  => $payload['company_id'] ?? null,
                'job_title'             => $payload['job_title'] ?? null,
                'language'              => $payload['language'] ?? null,
                'time_zone'             => $payload['time_zone'] ?? null,
                'address'               => $payload['address'] ?? null,
                'active'                => $payload['active'] ?? false,
                'view_all_tickets'      => $payload['view_all_tickets'] ?? false,
                'other_emails'          => $payload['other_emails'] ?? null,
                'other_companies'       => $payload['other_companies'] ?? null,
                'tags'                  => $payload['tags'] ?? null,
                'custom_fields'         => $payload['custom_fields'] ?? null,
                'payload'               => $payload,
                'fd_created_at'         => $payload['created_at'] ?? null,
                'fd_updated_at'         => $payload['updated_at'] ?? null,
                'synced_at'             => now(),
            ],
        );

        return $this->mapItem($item->fresh($this->withRelations));
    }

    public function mapItem($item)
    {
        if (empty($item)) {
            return null;
        }

        return [
            'id'                   => $item->id,
            'freshdesk_id'         => $item->freshdesk_id,
            'name'                 => $item->name,
            'email'                => $item->email,
            'phone'                => $item->phone,
            'mobile'               => $item->mobile,
            'company_id'           => $item->company_id,
            'freshdesk_company_id' => $item->freshdesk_company_id,
            'active'               => (bool) $item->active,
            'view_all_tickets'     => (bool) $item->view_all_tickets,
            'tags'                 => $item->tags,
            'fd_updated_at'        => $item->fd_updated_at,
            'Model'                => $item,
        ];
    }
}
