<?php

namespace Tests\Feature\Dashboard\System\Users;

use App\Models\AuditLog;
use App\Models\User;

class DeleteUserTest extends UserTestCase
{
    public function test_admin_can_delete_user(): void
    {
        $user = $this->makeUser([], 'manager');

        $res = $this->actingAs($this->admin())
            ->deleteJson(route('api.admin.system.users.destroy', $user->id));

        $res->assertOk();
        $res->assertJsonPath('data.id', $user->id);
        $res->assertJsonPath('data.deleted', true);

        // User model uses SoftDeletes — row remains but deleted_at is set.
        $this->assertNotNull(User::withTrashed()->find($user->id)->deleted_at);
        $this->assertNull(User::find($user->id));
    }

    public function test_deleted_user_disappears_from_list(): void
    {
        $keep = $this->makeUser(['email' => 'keep@example.test'], 'manager');
        $drop = $this->makeUser(['email' => 'drop@example.test'], 'manager');

        $this->actingAs($this->admin())
            ->deleteJson(route('api.admin.system.users.destroy', $drop->id))
            ->assertOk();

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.system.users.index', ['search' => '@example.test']));

        $res->assertOk();
        $emails = collect($res->json('data.data'))->pluck('email')->all();
        $this->assertContains('keep@example.test', $emails);
        $this->assertNotContains('drop@example.test', $emails);
    }

    public function test_delete_writes_audit_log(): void
    {
        $user = $this->makeUser([], 'manager');

        $this->actingAs($this->admin())
            ->deleteJson(route('api.admin.system.users.destroy', $user->id))
            ->assertOk();

        $log = AuditLog::where('action', 'user.deleted')
            ->where('target_id', $user->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('User', $log->target_type);
    }

    public function test_delete_returns_404_for_unknown_user(): void
    {
        $this->actingAs($this->admin())
            ->deleteJson(route('api.admin.system.users.destroy', 999999))
            ->assertNotFound();
    }

    public function test_manager_cannot_delete_user(): void
    {
        $user = $this->makeUser([], 'manager');

        $this->actingAs($this->manager())
            ->deleteJson(route('api.admin.system.users.destroy', $user->id))
            ->assertForbidden();
    }

    public function test_customer_cannot_delete_user(): void
    {
        $user = $this->makeUser([], 'manager');

        $this->actingAs($this->customer())
            ->deleteJson(route('api.admin.system.users.destroy', $user->id))
            ->assertForbidden();
    }

    public function test_unauthenticated_delete_is_rejected(): void
    {
        $user = $this->makeUser([], 'manager');

        $this->deleteJson(route('api.admin.system.users.destroy', $user->id))
            ->assertUnauthorized();
    }
}
