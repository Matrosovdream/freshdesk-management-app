<?php

namespace Tests\Feature\Dashboard\Profile;

use App\Models\User;
use App\Models\UserSetting;
use App\Services\UserSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function admin(): User
    {
        return User::where('email', 'admin@example.test')->firstOrFail();
    }

    protected function manager(): User
    {
        return User::where('email', 'manager@example.test')->firstOrFail();
    }

    // ----- /me ---------------------------------------------------------------

    public function test_me_returns_empty_preferences_object_when_none_saved(): void
    {
        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.auth.me'));

        $res->assertOk();
        $this->assertSame([], $res->json('data.preferences'));
    }

    public function test_me_returns_saved_preferences_decoded(): void
    {
        $admin = $this->admin();
        app(UserSettingsService::class)->set($admin, 'dark_theme', true);
        app(UserSettingsService::class)->set($admin, 'last_view', ['tab' => 'tickets']);

        $res = $this->actingAs($admin)->getJson(route('api.admin.auth.me'));

        $res->assertOk();
        $res->assertJsonPath('data.preferences.dark_theme', true);
        $res->assertJsonPath('data.preferences.last_view.tab', 'tickets');
    }

    // ----- GET /profile ------------------------------------------------------

    public function test_profile_show_returns_preferences(): void
    {
        $admin = $this->admin();
        app(UserSettingsService::class)->set($admin, 'dark_theme', true);

        $res = $this->actingAs($admin)->getJson(route('api.admin.profile.show'));

        $res->assertOk();
        $res->assertJsonPath('data.preferences.dark_theme', true);
    }

    // ----- PUT /profile (the dark mode toggle flow) --------------------------

    public function test_can_save_dark_theme_true_via_profile_update(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)
            ->putJson(route('api.admin.profile.update'), [
                'preferences' => ['dark_theme' => true],
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.preferences.dark_theme', true);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $admin->id,
            'key'     => 'dark_theme',
        ]);
        $this->assertSame(true, app(UserSettingsService::class)->get($admin->fresh(), 'dark_theme'));
    }

    public function test_can_save_dark_theme_false_via_profile_update(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)
            ->putJson(route('api.admin.profile.update'), [
                'preferences' => ['dark_theme' => false],
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.preferences.dark_theme', false);
        $this->assertSame(false, app(UserSettingsService::class)->get($admin->fresh(), 'dark_theme'));
    }

    public function test_subsequent_update_overrides_existing_preference(): void
    {
        $admin = $this->admin();

        // 1st: dark on
        $this->actingAs($admin)
            ->putJson(route('api.admin.profile.update'), [
                'preferences' => ['dark_theme' => true],
            ])->assertOk();

        // 2nd: dark off
        $this->actingAs($admin)
            ->putJson(route('api.admin.profile.update'), [
                'preferences' => ['dark_theme' => false],
            ])->assertOk();

        // Exactly one row, latest value
        $this->assertSame(1, UserSetting::where('user_id', $admin->id)->where('key', 'dark_theme')->count());
        $this->assertSame(false, app(UserSettingsService::class)->get($admin->fresh(), 'dark_theme'));
    }

    public function test_setting_a_preference_to_null_deletes_it(): void
    {
        $admin = $this->admin();
        app(UserSettingsService::class)->set($admin, 'dark_theme', true);

        $res = $this->actingAs($admin)
            ->putJson(route('api.admin.profile.update'), [
                'preferences' => ['dark_theme' => null],
            ]);

        $res->assertOk();
        $this->assertDatabaseMissing('user_settings', [
            'user_id' => $admin->id,
            'key'     => 'dark_theme',
        ]);
        $this->assertSame([], $res->json('data.preferences'));
    }

    public function test_other_preferences_are_preserved_when_one_changes(): void
    {
        $admin = $this->admin();
        $svc = app(UserSettingsService::class);
        $svc->set($admin, 'unrelated', 'keep-me');

        $this->actingAs($admin)
            ->putJson(route('api.admin.profile.update'), [
                'preferences' => ['dark_theme' => true],
            ])->assertOk();

        $this->assertSame('keep-me', $svc->get($admin->fresh(), 'unrelated'));
        $this->assertSame(true,      $svc->get($admin->fresh(), 'dark_theme'));
    }

    public function test_update_without_preferences_key_is_noop_for_settings(): void
    {
        $admin = $this->admin();
        app(UserSettingsService::class)->set($admin, 'dark_theme', true);

        $this->actingAs($admin)
            ->putJson(route('api.admin.profile.update'), ['name' => 'Renamed'])
            ->assertOk();

        $this->assertSame(true, app(UserSettingsService::class)->get($admin->fresh(), 'dark_theme'));
    }

    public function test_preferences_must_be_an_array(): void
    {
        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.profile.update'), [
                'preferences' => 'not-an-array',
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['preferences']);
    }

    public function test_dark_theme_must_be_boolean(): void
    {
        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.profile.update'), [
                'preferences' => ['dark_theme' => 'yes-please'],
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['preferences.dark_theme']);
    }

    public function test_manager_can_save_own_preferences(): void
    {
        $manager = $this->manager();

        $res = $this->actingAs($manager)
            ->putJson(route('api.admin.profile.update'), [
                'preferences' => ['dark_theme' => true],
            ]);

        $res->assertOk();
        $this->assertSame(true, app(UserSettingsService::class)->get($manager->fresh(), 'dark_theme'));
    }

    public function test_preferences_are_scoped_per_user(): void
    {
        $admin   = $this->admin();
        $manager = $this->manager();

        $this->actingAs($admin)
            ->putJson(route('api.admin.profile.update'), [
                'preferences' => ['dark_theme' => true],
            ])->assertOk();

        $this->actingAs($manager)
            ->putJson(route('api.admin.profile.update'), [
                'preferences' => ['dark_theme' => false],
            ])->assertOk();

        $svc = app(UserSettingsService::class);
        $this->assertSame(true,  $svc->get($admin->fresh(),   'dark_theme'));
        $this->assertSame(false, $svc->get($manager->fresh(), 'dark_theme'));
    }

    public function test_unauthenticated_update_is_rejected(): void
    {
        $this->putJson(route('api.admin.profile.update'), [
            'preferences' => ['dark_theme' => true],
        ])->assertUnauthorized();
    }
}
