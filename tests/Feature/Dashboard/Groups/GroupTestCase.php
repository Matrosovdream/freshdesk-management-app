<?php

namespace Tests\Feature\Dashboard\Groups;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class GroupTestCase extends TestCase
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

    protected function createGroup(array $overrides = []): Group
    {
        return Group::create(array_merge([
            'freshdesk_id'       => random_int(100000, 999999999),
            'name'               => 'Group '.uniqid(),
            'description'        => 'Test group',
            'auto_ticket_assign' => false,
            'agent_ids'          => [],
            'fd_created_at'      => now(),
            'fd_updated_at'      => now(),
        ], $overrides));
    }
}
