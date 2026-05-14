<?php

namespace Tests\Feature\Dashboard\Groups;

use App\Models\AuditLog;
use App\Models\Group;

class DeleteGroupTest extends GroupTestCase
{
    public function test_admin_can_delete_group(): void
    {
        $group = $this->createGroup(['name' => 'Delete Me']);

        $res = $this->actingAs($this->admin())
            ->deleteJson(route('api.admin.groups.destroy', $group->id));

        $res->assertOk();
        $res->assertJsonPath('data.id', $group->id);
        $res->assertJsonPath('data.deleted', true);

        $this->assertNull(Group::find($group->id));
    }

    public function test_deleted_group_disappears_from_list(): void
    {
        $keep = $this->createGroup(['name' => 'Keep']);
        $drop = $this->createGroup(['name' => 'Drop']);

        $this->actingAs($this->admin())
            ->deleteJson(route('api.admin.groups.destroy', $drop->id))
            ->assertOk();

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.groups.index'));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 1);
        $res->assertJsonPath('data.data.0.id', $keep->id);
    }

    public function test_delete_writes_audit_log(): void
    {
        $group = $this->createGroup();

        $this->actingAs($this->admin())
            ->deleteJson(route('api.admin.groups.destroy', $group->id))
            ->assertOk();

        $log = AuditLog::where('action', 'group.deleted')
            ->where('target_id', $group->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Group', $log->target_type);
    }

    public function test_delete_returns_404_for_unknown_group(): void
    {
        $res = $this->actingAs($this->admin())
            ->deleteJson(route('api.admin.groups.destroy', 999999));

        $res->assertNotFound();
    }

    public function test_manager_cannot_delete_group(): void
    {
        $group = $this->createGroup();

        $res = $this->actingAs($this->manager())
            ->deleteJson(route('api.admin.groups.destroy', $group->id));

        $res->assertForbidden();
    }

    public function test_customer_cannot_delete_group(): void
    {
        $group = $this->createGroup();

        $res = $this->actingAs($this->customer())
            ->deleteJson(route('api.admin.groups.destroy', $group->id));

        $res->assertForbidden();
    }

    public function test_unauthenticated_delete_is_rejected(): void
    {
        $group = $this->createGroup();

        $res = $this->deleteJson(route('api.admin.groups.destroy', $group->id));

        $res->assertUnauthorized();
    }
}
