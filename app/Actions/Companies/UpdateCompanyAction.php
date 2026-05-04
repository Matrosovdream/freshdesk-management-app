<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Support\AuditWriter;

final class UpdateCompanyAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $company = Company::findOrFail($id);
        $before = $company->toArray();

        $patch = array_intersect_key($data, array_flip([
            'name', 'description', 'domains', 'note', 'health_score', 'account_tier',
            'renewal_date', 'industry', 'custom_fields',
        ]));
        $company->fill($patch);
        $company->fd_updated_at = now();
        $company->save();

        AuditWriter::log('company.updated', 'Company', $company->id, $before, $company->fresh()->toArray());

        return $company->fresh()->toArray();
    }
}
