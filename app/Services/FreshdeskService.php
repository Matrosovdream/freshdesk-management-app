<?php

namespace App\Services;

use App\Mixins\Integrations\Freshdesk\Client;
use App\Mixins\Integrations\Freshdesk\RateLimitGuard;
use App\Mixins\Integrations\Freshdesk\Resources\Agents;
use App\Mixins\Integrations\Freshdesk\Resources\Automations;
use App\Mixins\Integrations\Freshdesk\Resources\BusinessHours;
use App\Mixins\Integrations\Freshdesk\Resources\Companies;
use App\Mixins\Integrations\Freshdesk\Resources\Contacts;
use App\Mixins\Integrations\Freshdesk\Resources\Conversations;
use App\Mixins\Integrations\Freshdesk\Resources\Groups;
use App\Mixins\Integrations\Freshdesk\Resources\Products;
use App\Mixins\Integrations\Freshdesk\Resources\Roles;
use App\Mixins\Integrations\Freshdesk\Resources\SlaPolicies;
use App\Mixins\Integrations\Freshdesk\Resources\TicketFields;
use App\Mixins\Integrations\Freshdesk\Resources\Tickets;
use App\Mixins\Integrations\Freshdesk\Resources\TimeEntries;

class FreshdeskService
{
    public function __construct(
        private Client $client,
        private RateLimitGuard $guard,
    ) {}

    public function tickets(): Tickets              { return new Tickets($this->client); }
    public function conversations(): Conversations  { return new Conversations($this->client); }
    public function timeEntries(): TimeEntries      { return new TimeEntries($this->client); }
    public function contacts(): Contacts            { return new Contacts($this->client); }
    public function companies(): Companies          { return new Companies($this->client); }
    public function agents(): Agents                { return new Agents($this->client); }
    public function groups(): Groups                { return new Groups($this->client); }
    public function roles(): Roles                  { return new Roles($this->client); }
    public function ticketFields(): TicketFields    { return new TicketFields($this->client); }
    public function products(): Products            { return new Products($this->client); }
    public function businessHours(): BusinessHours  { return new BusinessHours($this->client); }
    public function slaPolicies(): SlaPolicies      { return new SlaPolicies($this->client); }
    public function automations(): Automations      { return new Automations($this->client); }

    public function ping(): array
    {
        return $this->agents()->me();
    }

    public function rateLimitStatus(): ?array
    {
        return $this->guard->lastRemaining();
    }
}
