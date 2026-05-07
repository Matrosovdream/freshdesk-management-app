<?php

namespace Tests\Feature\Dashboard\Tickets;

use App\Support\AuditWriter;

class ListTicketActivityTest extends TicketTestCase
{
    public function test_activity_endpoint_returns_audit_entries_for_ticket(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->admin());
        AuditWriter::log('ticket.updated', 'Ticket', $ticket->id, ['status' => 2], ['status' => 4]);
        AuditWriter::log('ticket.assigned', 'Ticket', $ticket->id, [], []);

        $res = $this->getJson('/api/v1/admin/tickets/'.$ticket->id.'/activity');

        $res->assertOk();
        $res->assertJsonCount(2, 'data');
    }

    public function test_activity_endpoint_returns_empty_for_ticket_without_audit_logs(): void
    {
        $ticket = $this->createTicket();

        $res = $this->actingAs($this->admin())
            ->getJson('/api/v1/admin/tickets/'.$ticket->id.'/activity');

        $res->assertOk();
        $res->assertJsonCount(0, 'data');
    }

    public function test_activity_returns_404_for_unknown_ticket(): void
    {
        $res = $this->actingAs($this->admin())
            ->getJson('/api/v1/admin/tickets/999999/activity');

        $res->assertNotFound();
    }
}
