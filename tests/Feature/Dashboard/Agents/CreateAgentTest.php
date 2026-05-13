<?php

namespace Tests\Feature\Dashboard\Agents;

use App\Models\Agent;
use App\Models\AuditLog;

class CreateAgentTest extends AgentTestCase
{
    public function test_admin_can_create_an_agent(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/agents', [
                'email'        => 'newagent@example.test',
                'name'         => 'New Agent',
                'type'         => 'support_agent',
                'ticket_scope' => 1,
                'occasional'   => false,
                'signature'    => '<p>Cheers</p>',
                'group_ids'    => [1, 2],
                'role_ids'     => [10],
                'skill_ids'    => [],
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.email', 'newagent@example.test');
        $res->assertJsonPath('data.name', 'New Agent');
        $res->assertJsonPath('data.type', 'support_agent');

        $this->assertDatabaseHas('agents', ['email' => 'newagent@example.test']);
    }

    public function test_create_assigns_freshdesk_id_above_existing_max(): void
    {
        $this->createAgent(['freshdesk_id' => 5_000_000]);

        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/agents', ['email' => 'autofd@example.test']);

        $res->assertOk();
        $this->assertSame(5_000_001, (int) Agent::where('email', 'autofd@example.test')->value('freshdesk_id'));
    }

    public function test_create_uses_seed_freshdesk_id_when_table_empty(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/agents', ['email' => 'first@example.test']);

        $res->assertOk();
        $this->assertSame(1_000_000, (int) Agent::where('email', 'first@example.test')->value('freshdesk_id'));
    }

    public function test_create_writes_audit_log(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/agents', ['email' => 'audit@example.test'])
            ->assertOk();

        $log = AuditLog::where('action', 'agent.created')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('Agent', $log->target_type);
    }

    public function test_create_requires_email(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/agents', ['name' => 'No email']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['email']);
    }

    public function test_create_rejects_invalid_email(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/agents', ['email' => 'not-an-email']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['email']);
    }

    public function test_create_rejects_duplicate_email(): void
    {
        $this->createAgent(['email' => 'dup@example.test']);

        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/agents', ['email' => 'dup@example.test']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['email']);
    }

    public function test_create_rejects_out_of_range_ticket_scope(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/agents', [
                'email'        => 'scope@example.test',
                'ticket_scope' => 99,
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['ticket_scope']);
    }

    public function test_create_rejects_non_array_group_ids(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/agents', [
                'email'     => 'bad@example.test',
                'group_ids' => 'not-an-array',
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['group_ids']);
    }

    public function test_manager_cannot_create_agent(): void
    {
        $res = $this->actingAs($this->manager())
            ->postJson('/api/v1/admin/agents', ['email' => 'mgr@example.test']);

        $res->assertForbidden();
    }

    public function test_unauthenticated_create_is_rejected(): void
    {
        $res = $this->postJson('/api/v1/admin/agents', ['email' => 'anon@example.test']);

        $res->assertUnauthorized();
    }
}
