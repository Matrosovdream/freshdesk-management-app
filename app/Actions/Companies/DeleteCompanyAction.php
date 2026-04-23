<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Support\AuditWriter;

final class DeleteCompanyAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $c = Company::findOrFail($id);
        $c->delete();
        AuditWriter::log('company.deleted', 'Company', $id);
        return ['id' => $id, 'deleted' => true];
    }
}
