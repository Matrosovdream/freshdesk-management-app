<?php

namespace Tests\Feature\Dashboard\Agents;

class ListAgentsTest extends AgentTestCase
{
    public function test_admin_gets_paginated_list_with_meta_envelope(): void
    {
        $this->createAgent(['name' => 'A']);
        $this->createAgent(['name' => 'B']);
        $this->createAgent(['name' => 'C']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.agents.index'));

        $res->assertOk();
        $res->assertJsonStructure([
            'data' => [
                'data' => [['id', 'name', 'email', 'type', 'ticket_scope']],
                'meta' => ['total', 'next_cursor', 'per_page'],
            ],
        ]);
        $res->assertJsonPath('data.meta.total', 3);
    }

    public function test_returns_empty_list_when_no_agents_exist(): void
    {
        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.agents.index'));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 0);
        $res->assertJsonCount(0, 'data.data');
    }

    public function test_filter_by_type(): void
    {
        $this->createAgent(['name' => 'Sup A', 'type' => 'support_agent']);
        $this->createAgent(['name' => 'Sup B', 'type' => 'support_agent']);
        $this->createAgent(['name' => 'Field', 'type' => 'field_agent']);
        $this->createAgent(['name' => 'Collab','type' => 'collaborator']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.agents.index', ['type' => 'support_agent']));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 2);
    }

    public function test_filter_by_available(): void
    {
        $this->createAgent(['name' => 'On',  'available' => true]);
        $this->createAgent(['name' => 'On2', 'available' => true]);
        $this->createAgent(['name' => 'Off', 'available' => false]);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.agents.index', ['available' => 1]));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 2);
    }

    public function test_search_matches_name_case_insensitive(): void
    {
        $this->createAgent(['name' => 'Alice Smith', 'email' => 'alice@example.test']);
        $this->createAgent(['name' => 'ALICE Jones', 'email' => 'aj@example.test']);
        $this->createAgent(['name' => 'Bob',         'email' => 'bob@example.test']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.agents.index', ['search' => 'alice']));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 2);
    }

    public function test_search_matches_email(): void
    {
        $this->createAgent(['name' => 'Anon', 'email' => 'unique@example.test']);
        $this->createAgent(['name' => 'Foo',  'email' => 'foo@example.test']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.agents.index', ['search' => 'unique']));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 1);
        $res->assertJsonPath('data.data.0.email', 'unique@example.test');
    }

    public function test_autocomplete_param_works_like_search(): void
    {
        $this->createAgent(['name' => 'AC Tester', 'email' => 'ac@example.test']);
        $this->createAgent(['name' => 'Other',     'email' => 'other@example.test']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.agents.index', ['autocomplete' => 'ac']));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 1);
    }

    public function test_sort_by_name_ascending(): void
    {
        $b = $this->createAgent(['name' => 'Bravo']);
        $a = $this->createAgent(['name' => 'Alpha']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.agents.index', ['sort' => '+name']));

        $res->assertOk();
        $res->assertJsonPath('data.data.0.id', $a->id);
        $res->assertJsonPath('data.data.1.id', $b->id);
    }

    public function test_per_page_limits_results(): void
    {
        foreach (range(1, 5) as $i) {
            $this->createAgent(['name' => "Agent $i"]);
        }

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.agents.index', ['per_page' => 2]));

        $res->assertOk();
        $res->assertJsonCount(2, 'data.data');
        $res->assertJsonPath('data.meta.per_page', 2);
        $res->assertJsonPath('data.meta.total', 5);
        $res->assertJsonPath('data.meta.next_cursor', 2);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $res = $this->getJson(route('api.admin.agents.index'));

        $res->assertUnauthorized();
    }

    public function test_customer_role_cannot_access_admin_list(): void
    {
        $res = $this->actingAs($this->customer())
            ->getJson(route('api.admin.agents.index'));

        $res->assertForbidden();
    }
}
