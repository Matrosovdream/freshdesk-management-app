<?php

namespace App\Mixins\Integrations\Freshdesk\Testing;

class Scenarios
{
    public static function ticket(array $overrides = []): array
    {
        return array_merge([
            'id'           => 1001,
            'subject'      => 'Sample ticket',
            'description'  => '<p>Body</p>',
            'status'       => 2,
            'priority'     => 1,
            'source'       => 2,
            'requester_id' => 501,
            'responder_id' => 301,
            'group_id'     => 201,
            'created_at'   => '2026-04-22T10:00:00Z',
            'updated_at'   => '2026-04-22T10:00:00Z',
            'tags'         => [],
            'custom_fields' => [],
        ], $overrides);
    }

    public static function conversation(array $overrides = []): array
    {
        return array_merge([
            'id'             => 2001,
            'ticket_id'      => 1001,
            'user_id'        => 301,
            'body'           => '<p>Reply body</p>',
            'body_text'      => 'Reply body',
            'private'        => false,
            'incoming'       => false,
            'created_at'     => '2026-04-22T10:05:00Z',
            'updated_at'     => '2026-04-22T10:05:00Z',
            'attachments'    => [],
        ], $overrides);
    }

    public static function contact(array $overrides = []): array
    {
        return array_merge([
            'id'         => 501,
            'name'       => 'Jane Doe',
            'email'      => 'jane@example.com',
            'phone'      => null,
            'company_id' => 701,
            'active'     => true,
            'created_at' => '2026-01-01T00:00:00Z',
            'updated_at' => '2026-04-22T10:00:00Z',
        ], $overrides);
    }

    public static function company(array $overrides = []): array
    {
        return array_merge([
            'id'           => 701,
            'name'         => 'Acme Inc.',
            'domains'      => ['acme.com'],
            'description'  => null,
            'industry'     => null,
            'account_tier' => 'standard',
            'created_at'   => '2026-01-01T00:00:00Z',
            'updated_at'   => '2026-04-22T10:00:00Z',
        ], $overrides);
    }

    public static function agent(array $overrides = []): array
    {
        return array_merge([
            'id'         => 301,
            'contact'    => ['name' => 'Agent Smith', 'email' => 'smith@acme.com'],
            'available'  => true,
            'occasional' => false,
            'ticket_scope' => 1,
            'group_ids'  => [201],
            'role_ids'   => [1],
            'created_at' => '2026-01-01T00:00:00Z',
            'updated_at' => '2026-04-22T10:00:00Z',
        ], $overrides);
    }

    public static function group(array $overrides = []): array
    {
        return array_merge([
            'id'            => 201,
            'name'          => 'Billing',
            'description'   => null,
            'agent_ids'     => [301],
            'auto_ticket_assign' => 0,
            'created_at'    => '2026-01-01T00:00:00Z',
            'updated_at'    => '2026-04-22T10:00:00Z',
        ], $overrides);
    }

    public static function timeEntry(array $overrides = []): array
    {
        return array_merge([
            'id'           => 9001,
            'ticket_id'    => 1001,
            'agent_id'     => 301,
            'time_spent'   => '00:30',
            'billable'     => true,
            'executed_at'  => '2026-04-22T10:00:00Z',
            'timer_running' => false,
            'note'         => null,
        ], $overrides);
    }
}
