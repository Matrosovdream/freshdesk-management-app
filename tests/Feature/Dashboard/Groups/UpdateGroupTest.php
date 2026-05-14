<?php

namespace Tests\Feature\Dashboard\Groups;

use App\Models\AuditLog;

class UpdateGroupTest extends GroupTestCase
{
    public function test_admin_can_update_group_fields(): void
    {
        $group = $this->createGroup([
            'name'               => 'Old Name',
            'description'        => 'Old desc',
            'auto_ticket_assign' => false,
        ]);

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.groups.update', $group->id), [
                'name'               => 'New Name',
                'description'        => 'New desc',
                'auto_ticket_assign' => true,
                'unassigned_for'     => '1h',
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.name', 'New Name');
        $res->assertJsonPath('data.description', 'New desc');
        $res->assertJsonPath('data.auto_ticket_assign', true);

        $fresh = $group->fresh();
        $this->assertSame('New Name', $fresh->name);
        $this->assertTrue((bool) $fresh->auto_ticket_assign);
    }

    public function test_update_replaces_agent_ids_array(): void
    {
        $group = $this->createGroup(['agent_ids' => [1, 2]]);

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.groups.update', $group->id), ['agent_ids' => [7, 8, 9]]);

        $res->assertOk();
        $this->assertSame([7, 8, 9], $group->fresh()->agent_ids);
        $this->assertSame(3, $group->fresh()->agent_count);
    }

    public function test_update_ignores_freshdesk_id_in_payload(): void
    {
        $group = $this->createGroup(['freshdesk_id' => 12345]);

        $this->actingAs($this->admin())
            ->putJson(route('api.admin.groups.update', $group->id), [
                'name'         => 'Renamed',
                'freshdesk_id' => 99999,
            ])
            ->assertOk();

        $this->assertSame(12345, (int) $group->fresh()->freshdesk_id);
    }

    public function test_update_touches_fd_updated_at(): void
    {
        $group = $this->createGroup(['fd_updated_at' => '2026-01-01 00:00:00']);

        $this->actingAs($this->admin())
            ->putJson(route('api.admin.groups.update', $group->id), ['description' => 'Touched'])
            ->assertOk();

        $this->assertTrue($group->fresh()->fd_updated_at->greaterThan('2026-01-02 00:00:00'));
    }

    public function test_update_writes_audit_log_with_before_and_after(): void
    {
        $group = $this->createGroup(['name' => 'Before']);

        $this->actingAs($this->admin())
            ->putJson(route('api.admin.groups.update', $group->id), ['name' => 'After'])
            ->assertOk();

        $log = AuditLog::where('action', 'group.updated')
            ->where('target_id', $group->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Before', $log->payload_before['name'] ?? null);
        $this->assertSame('After',  $log->payload_after['name']  ?? null);
    }

    public function test_update_returns_404_for_unknown_group(): void
    {
        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.groups.update', 999999), ['name' => 'Nope']);

        $res->assertNotFound();
    }

    public function test_update_rejects_too_long_name(): void
    {
        $group = $this->createGroup();

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.groups.update', $group->id), ['name' => str_repeat('a', 121)]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['name']);
    }

    public function test_update_rejects_non_integer_escalate_to(): void
    {
        $group = $this->createGroup();

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.groups.update', $group->id), ['escalate_to' => 'foo']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['escalate_to']);
    }

    public function test_manager_cannot_update_group(): void
    {
        $group = $this->createGroup();

        $res = $this->actingAs($this->manager())
            ->putJson(route('api.admin.groups.update', $group->id), ['name' => 'Mgr edit']);

        $res->assertForbidden();
    }

    public function test_customer_cannot_update_group(): void
    {
        $group = $this->createGroup();

        $res = $this->actingAs($this->customer())
            ->putJson(route('api.admin.groups.update', $group->id), ['name' => 'Nope']);

        $res->assertForbidden();
    }

    public function test_unauthenticated_update_is_rejected(): void
    {
        $group = $this->createGroup();

        $res = $this->putJson(route('api.admin.groups.update', $group->id), ['name' => 'Anon']);

        $res->assertUnauthorized();
    }
}
