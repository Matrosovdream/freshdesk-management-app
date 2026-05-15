<?php

namespace Tests\Feature\Dashboard\System\Users;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

abstract class UserTestCase extends TestCase
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

    protected function customer(): User
    {
        return User::where('email', 'customer@example.test')->firstOrFail();
    }

    /**
     * Create a User with the given role slug attached.
     */
    protected function makeUser(array $overrides = [], ?string $roleSlug = 'manager'): User
    {
        $user = User::create(array_merge([
            'email'             => 'u'.uniqid().'@example.test',
            'name'              => 'Test User '.uniqid(),
            'password'          => Hash::make('password'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ], $overrides));

        if ($roleSlug) {
            $role = Role::where('slug', $roleSlug)->first();
            if ($role) $user->roles()->syncWithoutDetaching([$role->id]);
        }

        return $user;
    }
}
