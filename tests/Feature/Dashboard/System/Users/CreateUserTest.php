<?php

namespace Tests\Feature\Dashboard\System\Users;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUserTest extends UserTestCase
{
    public function test_admin_can_create_user_with_role_and_pin(): void
    {
        $managerRoleId = Role::where('slug', 'manager')->value('id');

        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.system.users.store'), [
                'email'    => 'new@example.test',
                'name'     => 'New User',
                'password' => 'secret-password',
                'pin'      => '1234',
                'role_ids' => [$managerRoleId],
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.email', 'new@example.test');
        $res->assertJsonPath('data.has_pin', true);
        $res->assertJsonPath('data.roles.0.slug', 'manager');
        $this->assertArrayNotHasKey('pin', $res->json('data'));

        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('1234', $user->pin));
        $this->assertTrue($user->hasRole('manager'));
    }

    public function test_pin_collision_returns_422_with_pin_error(): void
    {
        // Seed admin user already has PIN '9999'.
        $managerRoleId = Role::where('slug', 'manager')->value('id');

        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.system.users.store'), [
                'email'    => 'collide@example.test',
                'password' => 'secret-password',
                'pin'      => '9999',
                'role_ids' => [$managerRoleId],
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['pin']);
        $this->assertStringContainsString('already in use', $res->json('errors.pin.0'));

        $this->assertDatabaseMissing('users', ['email' => 'collide@example.test']);
    }

    public function test_pin_must_be_exactly_four_digits(): void
    {
        foreach (['12', '12345', 'abcd', '12a4'] as $bad) {
            $res = $this->actingAs($this->admin())
                ->postJson(route('api.admin.system.users.store'), [
                    'email'    => "bad{$bad}@example.test",
                    'password' => 'secret-password',
                    'pin'      => $bad,
                ]);

            $res->assertStatus(422);
            $res->assertJsonValidationErrors(['pin']);
        }
    }

    public function test_can_create_without_pin(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.system.users.store'), [
                'email'    => 'nopin@example.test',
                'password' => 'secret-password',
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.has_pin', false);

        $this->assertNull(User::where('email', 'nopin@example.test')->value('pin'));
    }

    public function test_email_must_be_unique(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.system.users.store'), [
                'email'    => 'admin@example.test',
                'password' => 'secret-password',
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['email']);
    }

    public function test_email_is_required(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.system.users.store'), [
                'password' => 'secret-password',
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['email']);
    }

    public function test_can_assign_multiple_roles(): void
    {
        $managerRoleId    = Role::where('slug', 'manager')->value('id');
        $customerRoleId   = Role::where('slug', 'customer')->value('id');

        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.system.users.store'), [
                'email'    => 'multi@example.test',
                'password' => 'secret-password',
                'role_ids' => [$managerRoleId, $customerRoleId],
            ]);

        $res->assertOk();
        $slugs = collect($res->json('data.roles'))->pluck('slug')->sort()->values()->all();
        $this->assertSame(['customer', 'manager'], $slugs);
    }

    public function test_invalid_role_ids_are_silently_dropped(): void
    {
        $managerRoleId = Role::where('slug', 'manager')->value('id');

        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.system.users.store'), [
                'email'    => 'partial@example.test',
                'password' => 'secret-password',
                'role_ids' => [$managerRoleId, 99999],
            ]);

        $res->assertOk();
        $res->assertJsonCount(1, 'data.roles');
        $res->assertJsonPath('data.roles.0.slug', 'manager');
    }

    public function test_create_writes_audit_log(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('api.admin.system.users.store'), [
                'email'    => 'audit@example.test',
                'password' => 'secret-password',
            ])
            ->assertOk();

        $log = AuditLog::where('action', 'user.created')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('User', $log->target_type);
        $this->assertSame('audit@example.test', $log->payload_after['email'] ?? null);
    }

    public function test_manager_cannot_create_user(): void
    {
        $this->actingAs($this->manager())
            ->postJson(route('api.admin.system.users.store'), [
                'email'    => 'mgr-try@example.test',
                'password' => 'secret-password',
            ])
            ->assertForbidden();
    }

    public function test_customer_cannot_create_user(): void
    {
        $this->actingAs($this->customer())
            ->postJson(route('api.admin.system.users.store'), [
                'email'    => 'cust-try@example.test',
                'password' => 'secret-password',
            ])
            ->assertForbidden();
    }

    public function test_unauthenticated_create_is_rejected(): void
    {
        $this->postJson(route('api.admin.system.users.store'), [
            'email'    => 'anon@example.test',
            'password' => 'secret-password',
        ])->assertUnauthorized();
    }
}
