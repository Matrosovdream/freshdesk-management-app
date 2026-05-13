<?php

namespace Tests\Feature\Dashboard\Groups;

use App\Models\AuditLog;
use App\Models\Group;

class CreateGroupTest extends GroupTestCase
{
    public function test_admin_can_create_a_group(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/groups', [
                'name'               => 'New Group',
                'description'        => 'Created by tests',
                'unassigned_for'     => '30m',
                'business_hour_id'   => 1,
                'escalate_to'        => 5,
                'agent_ids'          => [11, 22],
                'auto_ticket_assign' => true,
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.name', 'New Group');
        $res->assertJsonPath('data.description', 'Created by tests');
        $res->assertJsonPath('data.auto_ticket_assign', true);
        $res->assertJsonPath('data.agent_count', 2);

        $this->assertDatabaseHas('groups', ['name' => 'New Group']);
    }

    public function test_create_assigns_freshdesk_id_above_existing_max(): void
    {
        $this->createGroup(['freshdesk_id' => 5_000_000]);

        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/groups', ['name' => 'Auto FD ID']);

        $res->assertOk();
        $this->assertSame(5_000_001, (int) Group::where('name', 'Auto FD ID')->value('freshdesk_id'));
    }

    public function test_create_uses_seed_freshdesk_id_when_table_empty(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/groups', ['name' => 'First Group']);

        $res->assertOk();
        $this->assertSame(1_000_000, (int) Group::where('name', 'First Group')->value('freshdesk_id'));
    }

    public function test_create_writes_audit_log(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/groups', ['name' => 'Audited Group'])
            ->assertOk();

        $log = AuditLog::where('action', 'group.created')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('Group', $log->target_type);
    }

    public function test_create_requires_name(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/groups', ['description' => 'No name']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['name']);
    }

    public function test_create_rejects_too_long_name(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/groups', ['name' => str_repeat('a', 121)]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['name']);
    }

    public function test_create_rejects_non_array_agent_ids(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/groups', [
                'name'      => 'Bad Agents',
                'agent_ids' => 'not-an-array',
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['agent_ids']);
    }

    public function test_create_rejects_non_integer_business_hour_id(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/groups', [
                'name'             => 'Bad BH',
                'business_hour_id' => 'foo',
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['business_hour_id']);
    }

    public function test_manager_cannot_create_group(): void
    {
        $res = $this->actingAs($this->manager())
            ->postJson('/api/v1/admin/groups', ['name' => 'Mgr try']);

        $res->assertForbidden();
    }

    public function test_unauthenticated_create_is_rejected(): void
    {
        $res = $this->postJson('/api/v1/admin/groups', ['name' => 'Anon try']);

        $res->assertUnauthorized();
    }
}
