<?php

namespace App\Services\DummyData\Importers;

use App\Models\Company;
use App\Models\Contact;
use App\Repositories\People\ContactRepo;
use App\Services\DummyData\DummyDataLoader;

class ContactImporter
{
    public function __construct(
        private DummyDataLoader $loader,
        private ContactRepo $contactRepo,
    ) {}

    public function import(): int
    {
        $count = 0;

        foreach ($this->loader->load('contacts.json') as $payload) {
            if (empty($payload['id'])) {
                continue;
            }

            if ($this->contactRepo->getByFreshdeskId((int) $payload['id'])) {
                continue;
            }

            $this->contactRepo->upsertFromFreshdesk($payload);
            $count++;
        }

        $this->linkLocalCompanies();

        return $count;
    }

    private function linkLocalCompanies(): void
    {
        $map = Company::query()->pluck('id', 'freshdesk_id')->all();
        if (empty($map)) {
            return;
        }

        Contact::query()
            ->whereNotNull('freshdesk_company_id')
            ->whereNull('company_id')
            ->get()
            ->each(function (Contact $contact) use ($map) {
                $localId = $map[$contact->freshdesk_company_id] ?? null;
                if ($localId !== null) {
                    $contact->forceFill(['company_id' => $localId])->save();
                }
            });
    }
}
