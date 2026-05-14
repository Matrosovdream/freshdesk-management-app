<?php

namespace Tests\Feature\Dashboard\Tickets;

class ShowTicketTest extends TicketTestCase
{
    public function test_admin_can_view_a_ticket(): void
    {
        $ticket = $this->createTicket(['subject' => 'Login broken']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.tickets.show', $ticket->id));

        $res->assertOk();
        $res->assertJsonPath('data.id', $ticket->id);
        $res->assertJsonPath('data.subject', 'Login broken');
    }

    public function test_show_returns_404_for_unknown_ticket(): void
    {
        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.tickets.show', 999999));

        $res->assertNotFound();
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $ticket = $this->createTicket();

        $res = $this->getJson(route('api.admin.tickets.show', $ticket->id));

        $res->assertUnauthorized();
    }

    public function test_customer_role_cannot_access_admin_show_endpoint(): void
    {
        $ticket = $this->createTicket();

        $res = $this->actingAs($this->customer())
            ->getJson(route('api.admin.tickets.show', $ticket->id));

        $res->assertForbidden();
    }
}
