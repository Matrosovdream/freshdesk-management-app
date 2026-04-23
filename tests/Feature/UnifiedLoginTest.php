<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnifiedLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.connections.sqlite.database', database_path('database.sqlite'));
        DB::purge('sqlite');
    }

    public function test_superadmin_logs_in_via_portal_and_is_directed_to_dashboard(): void
    {
        $res = $this->withSession([])->postJson('/api/v1/portal/auth/login', [
            'email'    => 'admin@example.test',
            'password' => 'password',
        ]);
        $res->assertOk();
        $res->assertJsonPath('data.redirect_to', '/dashboard');
        $res->assertJsonPath('data.roles.0', 'superadmin');
    }

    public function test_manager_logs_in_via_portal_and_is_directed_to_dashboard(): void
    {
        $res = $this->withSession([])->postJson('/api/v1/portal/auth/login', [
            'email'    => 'manager@example.test',
            'password' => 'password',
        ]);
        $res->assertOk();
        $res->assertJsonPath('data.redirect_to', '/dashboard');
    }

    public function test_customer_logs_in_via_portal_and_stays_in_portal(): void
    {
        $res = $this->withSession([])->postJson('/api/v1/portal/auth/login', [
            'email'    => 'customer@example.test',
            'password' => 'password',
        ]);
        $res->assertOk();
        $res->assertJsonPath('data.redirect_to', '/portal');
        $res->assertJsonPath('data.roles.0', 'customer');
    }

    public function test_authenticated_user_can_fetch_identity_regardless_of_role(): void
    {
        foreach (['admin@example.test', 'manager@example.test', 'customer@example.test'] as $email) {
            $user = User::where('email', $email)->firstOrFail();
            $res = $this->actingAs($user)->getJson('/api/v1/portal/auth/me');
            $res->assertOk();
            $res->assertJsonPath('data.email', $email);
        }
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $res = $this->withSession([])->postJson('/api/v1/portal/auth/login', [
            'email'    => 'admin@example.test',
            'password' => 'wrong',
        ]);
        $res->assertStatus(422);
    }
}
