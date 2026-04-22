<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@example.test',
                'name'  => 'Super Admin',
                'role'  => 'superadmin',
            ],
            [
                'email' => 'manager@example.test',
                'name'  => 'Team Manager',
                'role'  => 'manager',
            ],
            [
                'email' => 'customer@example.test',
                'name'  => 'Test Customer',
                'role'  => 'customer',
            ],
        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name'              => $u['name'],
                    'password'          => Hash::make('password'),
                    'is_active'         => true,
                    'email_verified_at' => now(),
                ],
            );

            $role = Role::where('slug', $u['role'])->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        }
    }
}
