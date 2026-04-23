<?php

namespace App\Services\DummyData\Importers;

use App\Repositories\People\CompanyRepo;
use App\Services\DummyData\DummyDataLoader;

class CompanyImporter
{
    public function __construct(
        private DummyDataLoader $loader,
        private CompanyRepo $companyRepo,
    ) {}

    public function import(): int
    {
        $count = 0;

        foreach ($this->loader->load('companies.json') as $payload) {
            if (empty($payload['id'])) {
                continue;
            }

            $existing = $this->companyRepo->getByFreshdeskId((int) $payload['id']);
            if ($existing) {
                continue;
            }

            $this->companyRepo->upsertFromFreshdesk($payload);
            $count++;
        }

        return $count;
    }
}
