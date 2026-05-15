<?php

namespace Tests\Feature\Dashboard\System\Users;

use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UpdateUserTest extends UserTestCase
{
    public function test_admin_can_update_basic_fields(): void
    {
        $user = $this->makeUser(['name' => 'Old Name', 'email' => 'old@example.test'], 'manager');

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $user->id), [
                'name'      => 'New Name',
                'email'     => 'new@example.test',
                'is_active' => false,
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.name', 'New Name');
        $res->assertJsonPath('data.email', 'new@example.test');
        $res->assertJsonPath('data.is_active', false);

        $fresh = $user->fresh();
        $this->assertSame('New Name', $fresh->name);
        $this->assertFalse((bool) $fresh->is_active);
    }

    public function test_can_set_pin_on_user_that_had_none(): void
    {
        $user = $this->makeUser(['email' => 'pinless@example.test', 'pin' => null], 'manager');

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $user->id), ['pin' => '1234']);

        $res->assertOk();
        $res->assertJsonPath('data.has_pin', true);
        $this->assertTrue(Hash::check('1234', $user->fresh()->pin));
    }

    public function test_pin_collision_with_other_user_returns_422(): void
    {
        // admin@example.test has pin 9999 from seed
        $user = $this->makeUser(['email' => 'other@example.test', 'pin' => null], 'manager');

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $user->id), ['pin' => '9999']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['pin']);
        $this->assertStringContainsString('already in use', $res->json('errors.pin.0'));
        $this->assertNull($user->fresh()->pin);
    }

    public function test_saving_same_user_with_same_pin_does_not_trip_uniqueness(): void
    {
        // Admin already has PIN '9999'. Updating the admin and re-submitting '9999'
        // must NOT report a collision because we ignore the user's own row.
        $admin = $this->admin();

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $admin->id), [
                'name' => 'Admin Renamed',
                'pin'  => '9999',
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.name', 'Admin Renamed');
        $this->assertTrue(Hash::check('9999', $admin->fresh()->pin));
    }

    public function test_omitting_pin_preserves_existing_pin(): void
    {
        $admin = $this->admin(); // pin '9999'

        $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $admin->id), ['name' => 'Just Rename'])
            ->assertOk();

        $this->assertTrue(Hash::check('9999', $admin->fresh()->pin));
    }

    public function test_passing_null_pin_clears_existing_pin(): void
    {
        $admin = $this->admin(); // pin '9999'

        $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $admin->id), ['pin' => null])
            ->assertOk();

        $this->assertNull($admin->fresh()->pin);
    }

    public function test_pin_format_validation_on_update(): void
    {
        $user = $this->makeUser([], 'manager');

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $user->id), ['pin' => '12']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['pin']);
    }

    public function test_role_ids_replace_existing_roles(): void
    {
        $user = $this->makeUser([], 'manager');
        $customerRoleId = Role::where('slug', 'customer')->value('id');

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $user->id), [
                'role_ids' => [$customerRoleId],
            ]);

        $res->assertOk();
        $slugs = collect($res->json('data.roles'))->pluck('slug')->all();
        $this->assertSame(['customer'], $slugs);
        $this->assertFalse($user->fresh()->hasRole('manager'));
    }

    public function test_empty_role_ids_array_removes_all_roles(): void
    {
        $user = $this->makeUser([], 'manager');

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $user->id), [
                'role_ids' => [],
            ]);

        $res->assertOk();
        $this->assertCount(0, $res->json('data.roles'));
        $this->assertCount(0, $user->fresh()->roles);
    }

    public function test_email_must_remain_unique(): void
    {
        $a = $this->makeUser(['email' => 'a@example.test'], 'manager');
        $this->makeUser(['email' => 'b@example.test'], 'manager');

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $a->id), [
                'email' => 'b@example.test',
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_keep_own_email_on_update(): void
    {
        $user = $this->makeUser(['email' => 'same@example.test'], 'manager');

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $user->id), [
                'email' => 'same@example.test',
                'name'  => 'Renamed',
            ]);

        $res->assertOk();
    }

    public function test_password_is_only_changed_when_provided(): void
    {
        $user = $this->makeUser([], 'manager');
        $original = $user->password;

        $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $user->id), ['name' => 'Just Rename'])
            ->assertOk();

        $this->assertSame($original, $user->fresh()->password);

        $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $user->id), ['password' => 'brand-new-pass'])
            ->assertOk();

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_update_writes_audit_log(): void
    {
        $user = $this->makeUser(['name' => 'Before', 'email' => 'audit@example.test'], 'manager');

        $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', $user->id), ['name' => 'After'])
            ->assertOk();

        $log = AuditLog::where('action', 'user.updated')
            ->where('target_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Before', $log->payload_before['name'] ?? null);
        $this->assertSame('After',  $log->payload_after['name']  ?? null);
    }

    public function test_update_returns_404_for_unknown_user(): void
    {
        $this->actingAs($this->admin())
            ->putJson(route('api.admin.system.users.update', 999999), ['name' => 'Nope'])
            ->assertNotFound();
    }

    public function test_manager_cannot_update_user(): void
    {
        $user = $this->makeUser([], 'manager');

        $this->actingAs($this->manager())
            ->putJson(route('api.admin.system.users.update', $user->id), ['name' => 'Mgr'])
            ->assertForbidden();
    }

    public function test_customer_cannot_update_user(): void
    {
        $user = $this->makeUser([], 'manager');

        $this->actingAs($this->customer())
            ->putJson(route('api.admin.system.users.update', $user->id), ['name' => 'Cust'])
            ->assertForbidden();
    }

    public function test_unauthenticated_update_is_rejected(): void
    {
        $user = $this->makeUser([], 'manager');

        $this->putJson(route('api.admin.system.users.update', $user->id), ['name' => 'Anon'])
            ->assertUnauthorized();
    }
}
