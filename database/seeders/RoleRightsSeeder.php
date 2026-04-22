<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RoleRight;
use App\Support\Rights;
use Illuminate\Database\Seeder;

class RoleRightsSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'superadmin' => collect(Rights::catalog())
                ->flatten()
                ->reject(fn ($r) => str_starts_with($r, 'portal.'))
                ->values()
                ->all(),

            'manager' => [
                Rights::TICKETS_VIEW, Rights::TICKETS_CREATE, Rights::TICKETS_UPDATE,
                Rights::TICKETS_DELETE, Rights::TICKETS_RESTORE,
                Rights::TICKETS_BULK_UPDATE, Rights::TICKETS_MERGE,
                Rights::TICKETS_FORWARD, Rights::TICKETS_ASSIGN,
                Rights::CONVERSATIONS_REPLY, Rights::CONVERSATIONS_NOTE, Rights::CONVERSATIONS_UPDATE,
                Rights::CONTACTS_VIEW, Rights::CONTACTS_CREATE, Rights::CONTACTS_UPDATE,
                Rights::CONTACTS_DELETE, Rights::CONTACTS_RESTORE,
                Rights::CONTACTS_MERGE, Rights::CONTACTS_SEND_INVITE,
                Rights::COMPANIES_VIEW, Rights::COMPANIES_UPDATE,
                Rights::AGENTS_VIEW,
                Rights::GROUPS_VIEW,
                Rights::ROLES_VIEW,
                Rights::TIME_ENTRIES_VIEW, Rights::TIME_ENTRIES_CREATE, Rights::TIME_ENTRIES_UPDATE,
                Rights::REPORTS_VIEW, Rights::REPORTS_EXPORT,
            ],

            'customer' => [
                Rights::PORTAL_REQUESTS_VIEW_OWN, Rights::PORTAL_REQUESTS_VIEW_COMPANY,
                Rights::PORTAL_REQUESTS_CREATE, Rights::PORTAL_REQUESTS_REPLY,
                Rights::PORTAL_REQUESTS_RESOLVE, Rights::PORTAL_REQUESTS_REOPEN,
                Rights::PORTAL_REQUESTS_RATE, Rights::PORTAL_PROFILE_UPDATE,
            ],
        ];

        foreach ($map as $slug => $rights) {
            $role = Role::where('slug', $slug)->first();
            if (!$role) {
                continue;
            }

            RoleRight::where('role_id', $role->id)->delete();

            $rows = collect($rights)->unique()->map(fn ($r) => [
                'role_id'    => $role->id,
                'right'      => $r,
                'group'      => explode('.', $r)[0],
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            if (!empty($rows)) {
                RoleRight::insert($rows);
            }
        }
    }
}
