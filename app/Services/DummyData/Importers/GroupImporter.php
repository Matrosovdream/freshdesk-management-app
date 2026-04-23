<?php

namespace App\Services\DummyData\Importers;

use App\Repositories\Group\GroupRepo;
use App\Services\DummyData\DummyDataLoader;

class GroupImporter
{
    public function __construct(
        private DummyDataLoader $loader,
        private GroupRepo $groupRepo,
    ) {}

    public function import(): int
    {
        $count = 0;

        foreach ($this->loader->load('groups.json') as $payload) {
            if (empty($payload['id'])) {
                continue;
            }

            if ($this->groupRepo->getByFreshdeskId((int) $payload['id'])) {
                continue;
            }

            $this->groupRepo->upsertFromFreshdesk($payload);
            $count++;
        }

        return $count;
    }
}
