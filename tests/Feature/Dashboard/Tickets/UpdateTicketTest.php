<?php

namespace Tests\Feature\Dashboard\Tickets;

class UpdateTicketTest extends TicketTestCase
{
    public function test_admin_can_mark_ticket_as_spam(): void
    {
        $ticket = $this->createTicket(['spam' => false]);

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.tickets.update', $ticket->id), [
                'spam' => true,
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.spam', true);
        $this->assertTrue($ticket->fresh()->spam);
    }

    public function test_admin_can_unmark_spam(): void
    {
        $ticket = $this->createTicket(['spam' => true]);

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.tickets.update', $ticket->id), [
                'spam' => false,
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.spam', false);
        $this->assertFalse($ticket->fresh()->spam);
    }

    public function test_admin_can_change_status_and_priority(): void
    {
        $ticket = $this->createTicket(['status' => 2, 'priority' => 1]);

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.tickets.update', $ticket->id), [
                'status'   => 4,
                'priority' => 3,
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.status', 4);
        $res->assertJsonPath('data.priority', 3);
    }

    public function test_update_returns_404_for_unknown_ticket(): void
    {
        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.tickets.update', 999999), ['spam' => true]);

        $res->assertNotFound();
    }

    public function test_unauthenticated_update_is_rejected(): void
    {
        $ticket = $this->createTicket();

        $res = $this->putJson(route('api.admin.tickets.update', $ticket->id), ['spam' => true]);

        $res->assertUnauthorized();
    }
}
