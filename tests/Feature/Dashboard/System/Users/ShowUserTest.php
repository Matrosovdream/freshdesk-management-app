<?php

namespace Tests\Feature\Dashboard\System\Users;

class ShowUserTest extends UserTestCase
{
    public function test_admin_can_show_user_with_roles_and_has_pin(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)
            ->getJson(route('api.admin.system.users.show', $admin->id));

        $res->assertOk();
        $res->assertJsonPath('data.id', $admin->id);
        $res->assertJsonPath('data.email', 'admin@example.test');
        $res->assertJsonPath('data.has_pin', true);
        $res->assertJsonPath('data.roles.0.slug', 'superadmin');
        $this->assertArrayNotHasKey('pin', $res->json('data'));
    }

    public function test_show_returns_404_for_unknown_user(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('api.admin.system.users.show', 999999))
            ->assertNotFound();
    }

    public function test_manager_cannot_show_user(): void
    {
        $this->actingAs($this->manager())
            ->getJson(route('api.admin.system.users.show', $this->admin()->id))
            ->assertForbidden();
    }

    public function test_customer_cannot_show_user(): void
    {
        $this->actingAs($this->customer())
            ->getJson(route('api.admin.system.users.show', $this->admin()->id))
            ->assertForbidden();
    }

    public function test_unauthenticated_show_is_rejected(): void
    {
        $this->getJson(route('api.admin.system.users.show', $this->admin()->id))
            ->assertUnauthorized();
    }
}
