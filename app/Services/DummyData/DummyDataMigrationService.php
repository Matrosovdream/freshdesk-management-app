<?php

namespace App\Services\DummyData;

use App\Services\DummyData\Importers\AgentImporter;
use App\Services\DummyData\Importers\CompanyImporter;
use App\Services\DummyData\Importers\ContactImporter;
use App\Services\DummyData\Importers\ConversationImporter;
use App\Services\DummyData\Importers\GroupImporter;
use App\Services\DummyData\Importers\TicketImporter;
use App\Services\DummyData\Importers\TimeEntryImporter;
use App\Services\DummyData\Importers\UserLinkImporter;

class DummyDataMigrationService
{
    public function __construct(
        private CompanyImporter $companyImporter,
        private ContactImporter $contactImporter,
        private AgentImporter $agentImporter,
        private GroupImporter $groupImporter,
        private TicketImporter $ticketImporter,
        private ConversationImporter $conversationImporter,
        private TimeEntryImporter $timeEntryImporter,
        private UserLinkImporter $userLinkImporter,
    ) {}

    public function run(): array
    {
        $picker = new AssignmentPicker();

        return [
            'companies'     => $this->companyImporter->import(),
            'contacts'      => $this->contactImporter->import(),
            'agents'        => $this->agentImporter->import(),
            'groups'        => $this->groupImporter->import(),
            'tickets'       => $this->ticketImporter->import(),
            'conversations' => $this->conversationImporter->import(),
            'time_entries'  => $this->timeEntryImporter->import(),
            'user_links'    => $this->userLinkImporter->import($picker),
        ];
    }
}
