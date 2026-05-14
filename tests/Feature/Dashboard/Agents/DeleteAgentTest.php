<?php

namespace Tests\Feature\Dashboard\Agents;

use App\Models\Agent;
use App\Models\AuditLog;

class DeleteAgentTest extends AgentTestCase
{
    public function test_admin_can_delete_agent(): void
    {
        $agent = $this->createAgent(['name' => 'Delete Me']);

        $res = $this->actingAs($this->admin())
            ->deleteJson(route('api.admin.agents.destroy', $agent->id));

        $res->assertOk();
        $res->assertJsonPath('data.id', $agent->id);
        $res->assertJsonPath('data.deleted', true);

        $this->assertNull(Agent::find($agent->id));
    }

    public function test_deleted_agent_disappears_from_list(): void
    {
        $keep = $this->createAgent(['name' => 'Keep']);
        $drop = $this->createAgent(['name' => 'Drop']);

        $this->actingAs($this->admin())
            ->deleteJson(route('api.admin.agents.destroy', $drop->id))
            ->assertOk();

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.agents.index'));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 1);
        $res->assertJsonPath('data.data.0.id', $keep->id);
    }

    public function test_delete_writes_audit_log(): void
    {
        $agent = $this->createAgent();

        $this->actingAs($this->admin())
            ->deleteJson(route('api.admin.agents.destroy', $agent->id))
            ->assertOk();

        $log = AuditLog::where('action', 'agent.deleted')
            ->where('target_id', $agent->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Agent', $log->target_type);
    }

    public function test_delete_returns_404_for_unknown_agent(): void
    {
        $res = $this->actingAs($this->admin())
            ->deleteJson(route('api.admin.agents.destroy', 999999));

        $res->assertNotFound();
    }

    public function test_manager_cannot_delete_agent(): void
    {
        $agent = $this->createAgent();

        $res = $this->actingAs($this->manager())
            ->deleteJson(route('api.admin.agents.destroy', $agent->id));

        $res->assertForbidden();
    }

    public function test_customer_cannot_delete_agent(): void
    {
        $agent = $this->createAgent();

        $res = $this->actingAs($this->customer())
            ->deleteJson(route('api.admin.agents.destroy', $agent->id));

        $res->assertForbidden();
    }

    public function test_unauthenticated_delete_is_rejected(): void
    {
        $agent = $this->createAgent();

        $res = $this->deleteJson(route('api.admin.agents.destroy', $agent->id));

        $res->assertUnauthorized();
    }
}
