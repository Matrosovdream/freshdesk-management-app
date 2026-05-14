<?php

namespace Tests\Feature\Dashboard\Tickets;

class ListTicketsTest extends TicketTestCase
{
    public function test_admin_gets_paginated_list_with_meta_envelope(): void
    {
        $this->createTicket(['subject' => 'A']);
        $this->createTicket(['subject' => 'B']);
        $this->createTicket(['subject' => 'C']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.tickets.index'));

        $res->assertOk();
        $res->assertJsonStructure([
            'data' => [
                'data' => [['id', 'subject', 'status', 'priority']],
                'meta' => ['total', 'next_cursor', 'per_page'],
            ],
        ]);
        $res->assertJsonPath('data.meta.total', 3);
    }

    public function test_returns_empty_list_when_no_tickets_exist(): void
    {
        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.tickets.index'));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 0);
        $res->assertJsonCount(0, 'data.data');
    }

    public function test_filter_by_status(): void
    {
        $this->createTicket(['subject' => 'Open one',   'status' => 2]);
        $this->createTicket(['subject' => 'Open two',   'status' => 2]);
        $this->createTicket(['subject' => 'Resolved',   'status' => 4]);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.tickets.index', ['status' => 2]));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 2);
    }

    public function test_filter_by_priority(): void
    {
        $this->createTicket(['priority' => 1]);
        $this->createTicket(['priority' => 3]);
        $this->createTicket(['priority' => 3]);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.tickets.index', ['priority' => 3]));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 2);
    }

    public function test_filter_by_responder_id(): void
    {
        $agent = $this->createAgent();
        $this->createTicket(['responder_id' => $agent->id]);
        $this->createTicket(['responder_id' => null]);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.tickets.index', ['responder_id' => $agent->id]));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 1);
        $res->assertJsonPath('data.data.0.responder_id', $agent->id);
    }

    public function test_search_matches_subject(): void
    {
        $this->createTicket(['subject' => 'Login is broken']);
        $this->createTicket(['subject' => 'Cannot upload file']);
        $this->createTicket(['subject' => 'Slow login page']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.tickets.index', ['search' => 'login']));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 2);
    }

    public function test_sort_by_fd_created_at_descending(): void
    {
        $oldest = $this->createTicket(['subject' => 'Oldest', 'fd_created_at' => '2026-01-01 00:00:00']);
        $middle = $this->createTicket(['subject' => 'Middle', 'fd_created_at' => '2026-03-01 00:00:00']);
        $newest = $this->createTicket(['subject' => 'Newest', 'fd_created_at' => '2026-05-01 00:00:00']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.tickets.index', ['sort' => '-fd_created_at']));

        $res->assertOk();
        $res->assertJsonPath('data.data.0.id', $newest->id);
        $res->assertJsonPath('data.data.1.id', $middle->id);
        $res->assertJsonPath('data.data.2.id', $oldest->id);
    }

    public function test_sort_by_fd_created_at_ascending(): void
    {
        $oldest = $this->createTicket(['subject' => 'Oldest', 'fd_created_at' => '2026-01-01 00:00:00']);
        $newest = $this->createTicket(['subject' => 'Newest', 'fd_created_at' => '2026-05-01 00:00:00']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.tickets.index', ['sort' => '+fd_created_at']));

        $res->assertOk();
        $res->assertJsonPath('data.data.0.id', $oldest->id);
        $res->assertJsonPath('data.data.1.id', $newest->id);
    }

    public function test_per_page_limits_results(): void
    {
        foreach (range(1, 5) as $i) {
            $this->createTicket(['subject' => "Ticket $i"]);
        }

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.tickets.index', ['per_page' => 2]));

        $res->assertOk();
        $res->assertJsonCount(2, 'data.data');
        $res->assertJsonPath('data.meta.per_page', 2);
        $res->assertJsonPath('data.meta.total', 5);
        $res->assertJsonPath('data.meta.next_cursor', 2);
    }

    public function test_cursor_returns_next_page(): void
    {
        $tickets = [];
        foreach (range(1, 5) as $i) {
            $tickets[] = $this->createTicket([
                'subject'       => "Ticket $i",
                'fd_created_at' => "2026-01-0$i 00:00:00",
            ]);
        }

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.tickets.index', ['per_page' => 2, 'cursor' => 2, 'sort' => '+fd_created_at']));

        $res->assertOk();
        $res->assertJsonCount(2, 'data.data');
        $res->assertJsonPath('data.data.0.id', $tickets[2]->id);
        $res->assertJsonPath('data.data.1.id', $tickets[3]->id);
        $res->assertJsonPath('data.meta.next_cursor', 4);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $res = $this->getJson(route('api.admin.tickets.index'));

        $res->assertUnauthorized();
    }

    public function test_customer_role_cannot_access_admin_list(): void
    {
        $res = $this->actingAs($this->customer())
            ->getJson(route('api.admin.tickets.index'));

        $res->assertForbidden();
    }
}
