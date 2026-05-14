<?php

namespace Tests\Feature\Dashboard\Groups;

class ListGroupsTest extends GroupTestCase
{
    public function test_admin_gets_paginated_list_with_meta_envelope(): void
    {
        $this->createGroup(['name' => 'Alpha']);
        $this->createGroup(['name' => 'Bravo']);
        $this->createGroup(['name' => 'Charlie']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.groups.index'));

        $res->assertOk();
        $res->assertJsonStructure([
            'data' => [
                'data' => [['id', 'name', 'description', 'agent_count', 'auto_ticket_assign']],
                'meta' => ['total', 'next_cursor', 'per_page'],
            ],
        ]);
        $res->assertJsonPath('data.meta.total', 3);
    }

    public function test_returns_empty_list_when_no_groups_exist(): void
    {
        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.groups.index'));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 0);
        $res->assertJsonCount(0, 'data.data');
    }

    public function test_search_matches_name_case_insensitive(): void
    {
        $this->createGroup(['name' => 'Support Team']);
        $this->createGroup(['name' => 'SUPPORT Tier 2']);
        $this->createGroup(['name' => 'Sales']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.groups.index', ['search' => 'support']));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 2);
    }

    public function test_agent_count_reflects_agent_ids_length(): void
    {
        $group = $this->createGroup(['name' => 'Counted', 'agent_ids' => [10, 20, 30]]);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.groups.index', ['search' => 'Counted']));

        $res->assertOk();
        $res->assertJsonPath('data.data.0.id', $group->id);
        $res->assertJsonPath('data.data.0.agent_count', 3);
    }

    public function test_sort_by_name_ascending(): void
    {
        $b = $this->createGroup(['name' => 'Bravo']);
        $a = $this->createGroup(['name' => 'Alpha']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.groups.index', ['sort' => '+name']));

        $res->assertOk();
        $res->assertJsonPath('data.data.0.id', $a->id);
        $res->assertJsonPath('data.data.1.id', $b->id);
    }

    public function test_sort_by_name_descending_default_dir(): void
    {
        $a = $this->createGroup(['name' => 'Alpha']);
        $b = $this->createGroup(['name' => 'Bravo']);
        $c = $this->createGroup(['name' => 'Charlie']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.groups.index', ['sort' => 'name']));

        $res->assertOk();
        $res->assertJsonPath('data.data.0.id', $c->id);
        $res->assertJsonPath('data.data.1.id', $b->id);
        $res->assertJsonPath('data.data.2.id', $a->id);
    }

    public function test_per_page_limits_results(): void
    {
        foreach (range(1, 5) as $i) {
            $this->createGroup(['name' => "Group $i"]);
        }

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.groups.index', ['per_page' => 2]));

        $res->assertOk();
        $res->assertJsonCount(2, 'data.data');
        $res->assertJsonPath('data.meta.per_page', 2);
        $res->assertJsonPath('data.meta.total', 5);
        $res->assertJsonPath('data.meta.next_cursor', 2);
    }

    public function test_cursor_returns_next_page(): void
    {
        foreach (range(1, 5) as $i) {
            $this->createGroup(['name' => "Group $i"]);
        }

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.groups.index', ['per_page' => 2, 'cursor' => 2, 'sort' => '+name']));

        $res->assertOk();
        $res->assertJsonCount(2, 'data.data');
        $res->assertJsonPath('data.meta.next_cursor', 4);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $res = $this->getJson(route('api.admin.groups.index'));

        $res->assertUnauthorized();
    }

    public function test_customer_role_cannot_access_admin_list(): void
    {
        $res = $this->actingAs($this->customer())
            ->getJson(route('api.admin.groups.index'));

        $res->assertForbidden();
    }
}
