<?php

namespace App\Actions\Companies;

use App\Models\Company;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetCompanyAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $c = Company::withTrashed()->find($id);
        if (! $c) throw new NotFoundHttpException('Company not found.');
        return $c->toArray();
    }
}
