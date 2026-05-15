<?php

namespace Tests\Feature\Dashboard\System\Users;

class ListUsersTest extends UserTestCase
{
    public function test_admin_lists_users_with_roles_and_has_pin_flag(): void
    {
        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.system.users.index'));

        $res->assertOk();
        $res->assertJsonStructure([
            'data' => [
                'data' => [['id', 'email', 'name', 'is_active', 'has_pin', 'roles', 'assigned_groups']],
                'meta' => ['total', 'next_cursor', 'per_page'],
            ],
        ]);

        // Seed creates 3 users (admin, manager, customer). All have PINs.
        $res->assertJsonPath('data.meta.total', 3);

        $adminRow = collect($res->json('data.data'))->firstWhere('email', 'admin@example.test');
        $this->assertNotNull($adminRow);
        $this->assertTrue($adminRow['has_pin']);
        $this->assertSame(['superadmin'], array_column($adminRow['roles'], 'slug'));
    }

    public function test_response_never_exposes_raw_pin_hash(): void
    {
        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.system.users.index'));

        $res->assertOk();
        foreach ($res->json('data.data') as $row) {
            $this->assertArrayNotHasKey('pin', $row);
        }
    }

    public function test_has_pin_is_false_when_user_has_no_pin(): void
    {
        $user = $this->makeUser(['email' => 'nopin@example.test', 'pin' => null], 'manager');

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.system.users.index', ['search' => 'nopin']));

        $res->assertOk();
        $row = collect($res->json('data.data'))->firstWhere('id', $user->id);
        $this->assertNotNull($row);
        $this->assertFalse($row['has_pin']);
    }

    public function test_search_matches_email_case_insensitive(): void
    {
        $this->makeUser(['email' => 'alpha@example.test', 'name' => 'Alpha'], 'manager');

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.system.users.index', ['search' => 'ALPHA']));

        $res->assertOk();
        $this->assertSame(1, $res->json('data.meta.total'));
        $res->assertJsonPath('data.data.0.email', 'alpha@example.test');
    }

    public function test_per_page_limits_results(): void
    {
        foreach (range(1, 5) as $i) {
            $this->makeUser(['email' => "extra{$i}@example.test"], 'manager');
        }

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.system.users.index', ['per_page' => 2]));

        $res->assertOk();
        $res->assertJsonCount(2, 'data.data');
        $res->assertJsonPath('data.meta.per_page', 2);
        $res->assertJsonPath('data.meta.total', 8); // 3 seeded + 5 created
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson(route('api.admin.system.users.index'))->assertUnauthorized();
    }

    public function test_manager_cannot_list_users(): void
    {
        $this->actingAs($this->manager())
            ->getJson(route('api.admin.system.users.index'))
            ->assertForbidden();
    }

    public function test_customer_cannot_list_users(): void
    {
        $this->actingAs($this->customer())
            ->getJson(route('api.admin.system.users.index'))
            ->assertForbidden();
    }
}
