<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Support\ApiQuery;
use App\Support\ManagerScope;

final class ListCompaniesAction
{
    public function handle(array $data = []): array
    {
        $q = Company::query()
            ->withCount(['tickets as open_tickets_count' => function ($t) {
                $t->whereIn('status', [2, 3]);
            }]);
        ManagerScope::applyToCompanies($q);

        if (!empty($data['industry']))     $q->where('industry', $data['industry']);
        if (!empty($data['account_tier'])) $q->where('account_tier', $data['account_tier']);
        if (!empty($data['domain'])) {
            $q->whereJsonContains('domains', $data['domain']);
        }

        ApiQuery::applySearch($q, $data['search'] ?? null, ['name']);
        ApiQuery::applyOrderBy($q, $data['sort'] ?? null, ['name', 'fd_updated_at', 'renewal_date'], 'name');

        return ApiQuery::page($q, $data);
    }
}
