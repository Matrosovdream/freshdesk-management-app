<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Support\AuditWriter;

final class CreateCompanyAction
{
    public function handle(array $data = []): array
    {
        $max = (int) Company::max('freshdesk_id');
        $payload = array_intersect_key($data, array_flip([
            'name', 'description', 'domains', 'note', 'health_score', 'account_tier',
            'renewal_date', 'industry', 'custom_fields',
        ]));
        $payload['freshdesk_id']  = $max > 0 ? $max + 1 : 1_000_000;
        $payload['fd_created_at'] = now();
        $payload['fd_updated_at'] = now();

        $c = Company::create($payload);
        AuditWriter::log('company.created', 'Company', $c->id, [], $c->toArray());
        return $c->toArray();
    }
}
