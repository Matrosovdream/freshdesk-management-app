<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Support\AuditWriter;

final class UpdateCompanyAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $c = Company::findOrFail($id);
        $before = $c->toArray();

        $patch = array_intersect_key($data, array_flip([
            'name', 'description', 'domains', 'note', 'health_score', 'account_tier',
            'renewal_date', 'industry', 'custom_fields',
        ]));
        $c->fill($patch);
        $c->fd_updated_at = now();
        $c->save();

        AuditWriter::log('company.updated', 'Company', $c->id, $before, $c->fresh()->toArray());
        return $c->fresh()->toArray();
    }
}
