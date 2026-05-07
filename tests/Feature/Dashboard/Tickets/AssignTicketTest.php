<?php

namespace Tests\Feature\Dashboard\Tickets;

class AssignTicketTest extends TicketTestCase
{
    public function test_admin_can_assign_an_agent(): void
    {
        $ticket = $this->createTicket(['responder_id' => null]);
        $agent  = $this->createAgent();

        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/tickets/'.$ticket->id.'/assign', [
                'responder_id' => $agent->id,
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.responder_id', $agent->id);
        $this->assertSame($agent->id, (int) $ticket->fresh()->responder_id);
    }

    public function test_admin_can_unassign_by_passing_null(): void
    {
        $agent  = $this->createAgent();
        $ticket = $this->createTicket(['responder_id' => $agent->id]);

        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/tickets/'.$ticket->id.'/assign', [
                'responder_id' => null,
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.responder_id', null);
        $this->assertNull($ticket->fresh()->responder_id);
    }

    public function test_assign_returns_404_for_unknown_ticket(): void
    {
        $agent = $this->createAgent();

        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/tickets/999999/assign', [
                'responder_id' => $agent->id,
            ]);

        $res->assertNotFound();
    }
}
