<?php

namespace Tests\Feature\Dashboard\Agents;

use App\Models\AuditLog;

class UpdateAgentTest extends AgentTestCase
{
    public function test_admin_can_update_agent_fields(): void
    {
        $agent = $this->createAgent([
            'name'         => 'Old Name',
            'type'         => 'support_agent',
            'ticket_scope' => 1,
            'available'    => true,
        ]);

        $res = $this->actingAs($this->admin())
            ->putJson('/api/v1/admin/agents/'.$agent->id, [
                'name'         => 'New Name',
                'type'         => 'field_agent',
                'ticket_scope' => 3,
                'available'    => false,
                'job_title'    => 'Senior',
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.name', 'New Name');
        $res->assertJsonPath('data.type', 'field_agent');
        $res->assertJsonPath('data.ticket_scope', 3);
        $res->assertJsonPath('data.available', false);
        $res->assertJsonPath('data.job_title', 'Senior');

        $fresh = $agent->fresh();
        $this->assertSame('New Name', $fresh->name);
        $this->assertSame('field_agent', $fresh->type);
        $this->assertFalse((bool) $fresh->available);
    }

    public function test_update_replaces_group_ids_array(): void
    {
        $agent = $this->createAgent(['group_ids' => [1, 2]]);

        $res = $this->actingAs($this->admin())
            ->putJson('/api/v1/admin/agents/'.$agent->id, ['group_ids' => [5, 6, 7]]);

        $res->assertOk();
        $this->assertSame([5, 6, 7], $agent->fresh()->group_ids);
    }

    public function test_update_ignores_freshdesk_id_in_payload(): void
    {
        $agent = $this->createAgent(['freshdesk_id' => 12345]);

        $this->actingAs($this->admin())
            ->putJson('/api/v1/admin/agents/'.$agent->id, [
                'name'         => 'Renamed',
                'freshdesk_id' => 99999,
            ])
            ->assertOk();

        $this->assertSame(12345, (int) $agent->fresh()->freshdesk_id);
    }

    public function test_update_touches_fd_updated_at(): void
    {
        $agent = $this->createAgent(['fd_updated_at' => '2026-01-01 00:00:00']);

        $this->actingAs($this->admin())
            ->putJson('/api/v1/admin/agents/'.$agent->id, ['name' => 'Touched'])
            ->assertOk();

        $this->assertTrue($agent->fresh()->fd_updated_at->greaterThan('2026-01-02 00:00:00'));
    }

    public function test_update_writes_audit_log_with_before_and_after(): void
    {
        $agent = $this->createAgent(['name' => 'Before']);

        $this->actingAs($this->admin())
            ->putJson('/api/v1/admin/agents/'.$agent->id, ['name' => 'After'])
            ->assertOk();

        $log = AuditLog::where('action', 'agent.updated')
            ->where('target_id', $agent->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Before', $log->payload_before['name'] ?? null);
        $this->assertSame('After',  $log->payload_after['name']  ?? null);
    }

    public function test_update_returns_404_for_unknown_agent(): void
    {
        $res = $this->actingAs($this->admin())
            ->putJson('/api/v1/admin/agents/999999', ['name' => 'Nope']);

        $res->assertNotFound();
    }

    public function test_update_rejects_invalid_email(): void
    {
        $agent = $this->createAgent();

        $res = $this->actingAs($this->admin())
            ->putJson('/api/v1/admin/agents/'.$agent->id, ['email' => 'not-email']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['email']);
    }

    public function test_update_rejects_out_of_range_ticket_scope(): void
    {
        $agent = $this->createAgent();

        $res = $this->actingAs($this->admin())
            ->putJson('/api/v1/admin/agents/'.$agent->id, ['ticket_scope' => 9]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['ticket_scope']);
    }

    public function test_manager_cannot_update_agent(): void
    {
        $agent = $this->createAgent();

        $res = $this->actingAs($this->manager())
            ->putJson('/api/v1/admin/agents/'.$agent->id, ['name' => 'Mgr edit']);

        $res->assertForbidden();
    }

    public function test_customer_cannot_update_agent(): void
    {
        $agent = $this->createAgent();

        $res = $this->actingAs($this->customer())
            ->putJson('/api/v1/admin/agents/'.$agent->id, ['name' => 'Nope']);

        $res->assertForbidden();
    }

    public function test_unauthenticated_update_is_rejected(): void
    {
        $agent = $this->createAgent();

        $res = $this->putJson('/api/v1/admin/agents/'.$agent->id, ['name' => 'Anon']);

        $res->assertUnauthorized();
    }
}
