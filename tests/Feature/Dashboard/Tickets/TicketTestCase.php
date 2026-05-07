<?php

namespace Tests\Feature\Dashboard\Tickets;

use App\Models\Agent;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class TicketTestCase extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function admin(): User
    {
        return User::where('email', 'admin@example.test')->firstOrFail();
    }

    protected function manager(): User
    {
        return User::where('email', 'manager@example.test')->firstOrFail();
    }

    protected function customer(): User
    {
        return User::where('email', 'customer@example.test')->firstOrFail();
    }

    protected function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'freshdesk_id'     => random_int(100000, 999999999),
            'subject'          => 'Test ticket',
            'description'      => 'Test description',
            'description_text' => 'Test description',
            'status'           => 2,
            'priority'         => 1,
        ], $overrides));
    }

    protected function createAgent(array $overrides = []): Agent
    {
        return Agent::create(array_merge([
            'freshdesk_id' => random_int(100000, 999999999),
            'email'        => 'agent_'.uniqid().'@example.test',
            'name'         => 'Test Agent',
        ], $overrides));
    }
}
