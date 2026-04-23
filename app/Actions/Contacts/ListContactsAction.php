<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Support\ApiQuery;
use App\Support\ManagerScope;

final class ListContactsAction
{
    public function handle(array $data = []): array
    {
        $q = Contact::query()->with('company');
        ManagerScope::applyToContacts($q);

        if (!empty($data['state'])) {
            switch ($data['state']) {
                case 'verified':   $q->where('active', true); break;
                case 'unverified': $q->where('active', false); break;
                case 'deleted':    $q->onlyTrashed(); break;
            }
        }
        if (!empty($data['company_id'])) $q->where('company_id', (int) $data['company_id']);
        if (!empty($data['tag'])) {
            foreach ((array) $data['tag'] as $t) $q->whereJsonContains('tags', $t);
        }
        if (!empty($data['updated_since'])) $q->where('fd_updated_at', '>=', $data['updated_since']);

        ApiQuery::applySearch($q, $data['search'] ?? ($data['autocomplete'] ?? null), ['name', 'email', 'phone', 'mobile']);
        ApiQuery::applyOrderBy($q, $data['sort'] ?? null, ['name', 'fd_updated_at', 'fd_created_at'], 'fd_updated_at');

        return ApiQuery::page($q, $data);
    }
}
