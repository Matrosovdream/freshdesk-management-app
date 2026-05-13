<?php

namespace Tests\Feature\Dashboard\Agents;

class ShowAgentTest extends AgentTestCase
{
    public function test_admin_can_view_an_agent(): void
    {
        $agent = $this->createAgent([
            'name'         => 'Showme Agent',
            'email'        => 'showme@example.test',
            'type'         => 'support_agent',
            'ticket_scope' => 2,
        ]);

        $res = $this->actingAs($this->admin())
            ->getJson('/api/v1/admin/agents/'.$agent->id);

        $res->assertOk();
        $res->assertJsonPath('data.id', $agent->id);
        $res->assertJsonPath('data.name', 'Showme Agent');
        $res->assertJsonPath('data.email', 'showme@example.test');
        $res->assertJsonPath('data.type', 'support_agent');
        $res->assertJsonPath('data.ticket_scope', 2);
    }

    public function test_show_returns_appended_attributes(): void
    {
        $agent = $this->createAgent([
            'fd_updated_at' => '2026-04-10 12:00:00',
            'payload'       => ['avatar' => ['avatar_url' => 'https://cdn.test/a.png']],
        ]);

        $res = $this->actingAs($this->admin())
            ->getJson('/api/v1/admin/agents/'.$agent->id);

        $res->assertOk();
        $res->assertJsonPath('data.avatar_url', 'https://cdn.test/a.png');
        $this->assertNotNull($res->json('data.last_login_at'));
    }

    public function test_show_returns_404_for_unknown_agent(): void
    {
        $res = $this->actingAs($this->admin())
            ->getJson('/api/v1/admin/agents/999999');

        $res->assertNotFound();
    }

    public function test_unauthenticated_show_is_rejected(): void
    {
        $agent = $this->createAgent();

        $res = $this->getJson('/api/v1/admin/agents/'.$agent->id);

        $res->assertUnauthorized();
    }

    public function test_customer_cannot_view_agent(): void
    {
        $agent = $this->createAgent();

        $res = $this->actingAs($this->customer())
            ->getJson('/api/v1/admin/agents/'.$agent->id);

        $res->assertForbidden();
    }
}
