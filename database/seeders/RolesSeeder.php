<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'slug'        => 'superadmin',
                'name'        => 'Superadmin',
                'description' => 'Full access to dashboard and system configuration.',
            ],
            [
                'slug'        => 'manager',
                'name'        => 'Manager',
                'description' => 'Dashboard access scoped to assigned groups.',
            ],
            [
                'slug'        => 'customer',
                'name'        => 'Customer',
                'description' => 'Portal-only access for end users.',
            ],
        ];

        foreach ($roles as $r) {
            Role::updateOrCreate(
                ['slug' => $r['slug']],
                [
                    'name'        => $r['name'],
                    'description' => $r['description'],
                    'is_system'   => true,
                ],
            );
        }
    }
}
