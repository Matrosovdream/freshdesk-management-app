<?php

namespace App\Services\DummyData\Importers;

use App\Repositories\People\AgentRepo;
use App\Services\DummyData\DummyDataLoader;

class AgentImporter
{
    public function __construct(
        private DummyDataLoader $loader,
        private AgentRepo $agentRepo,
    ) {}

    public function import(): int
    {
        $count = 0;

        foreach ($this->loader->load('agents.json') as $payload) {
            if (empty($payload['id'])) {
                continue;
            }

            if ($this->agentRepo->getByFreshdeskId((int) $payload['id'])) {
                continue;
            }

            $this->agentRepo->upsertFromFreshdesk($payload);
            $count++;
        }

        return $count;
    }
}
