<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Freshdesk connection (edited via /dashboard/system/freshdesk)
            ['key' => 'freshdesk.domain',   'value' => '',  'type' => 'string',    'group' => 'freshdesk', 'description' => 'e.g. acme.freshdesk.com'],
            ['key' => 'freshdesk.api_key',  'value' => '',  'type' => 'encrypted', 'group' => 'freshdesk', 'description' => 'API key (encrypted at rest)'],
            ['key' => 'freshdesk.test_ok',  'value' => '0', 'type' => 'bool',      'group' => 'freshdesk', 'description' => 'Last Test connection result'],

            // App
            ['key' => 'app.name',       'value' => 'Freshdesk Manager', 'type' => 'string', 'group' => 'general', 'description' => null],
            ['key' => 'app.default_tz', 'value' => 'UTC',               'type' => 'string', 'group' => 'general', 'description' => null],
            ['key' => 'app.portal_url', 'value' => '',                  'type' => 'string', 'group' => 'general', 'description' => 'Public portal origin'],

            // Portal
            ['key' => 'portal.allow_public_registration', 'value' => '0', 'type' => 'bool', 'group' => 'portal', 'description' => null],
            ['key' => 'portal.require_captcha',           'value' => '0', 'type' => 'bool', 'group' => 'portal', 'description' => null],
            ['key' => 'portal.csat_on_resolve',           'value' => '1', 'type' => 'bool', 'group' => 'portal', 'description' => null],

            // Sync
            ['key' => 'sync.tickets_interval',   'value' => '2',  'type' => 'int', 'group' => 'sync', 'description' => null],
            ['key' => 'sync.contacts_interval',  'value' => '10', 'type' => 'int', 'group' => 'sync', 'description' => null],
            ['key' => 'sync.companies_interval', 'value' => '30', 'type' => 'int', 'group' => 'sync', 'description' => null],
            ['key' => 'sync.agents_interval',    'value' => '30', 'type' => 'int', 'group' => 'sync', 'description' => null],
            ['key' => 'sync.groups_interval',    'value' => '60', 'type' => 'int', 'group' => 'sync', 'description' => null],

            // Notifications
            ['key' => 'notify.slack_webhook', 'value' => '', 'type' => 'encrypted', 'group' => 'notifications', 'description' => null],
            ['key' => 'notify.daily_digest',  'value' => '0','type' => 'bool',       'group' => 'notifications', 'description' => null],
        ];

        foreach ($defaults as $d) {
            Setting::updateOrCreate(['key' => $d['key']], $d);
        }
    }
}
