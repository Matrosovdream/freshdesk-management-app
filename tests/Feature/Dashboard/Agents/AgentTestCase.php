<?php

namespace Tests\Feature\Dashboard\Agents;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class AgentTestCase extends TestCase
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

    protected function createAgent(array $overrides = []): Agent
    {
        return Agent::create(array_merge([
            'freshdesk_id'  => random_int(100000, 999999999),
            'email'         => 'agent_'.uniqid().'@example.test',
            'name'          => 'Test Agent',
            'type'          => 'support_agent',
            'ticket_scope'  => 1,
            'available'     => true,
            'occasional'    => false,
            'fd_created_at' => now(),
            'fd_updated_at' => now(),
        ], $overrides));
    }
}
