<?php

namespace Tests\Feature\Dashboard\Agents;

use App\Models\Agent;
use Illuminate\Http\UploadedFile;

class BulkCreateAgentsTest extends AgentTestCase
{
    private function csvFile(string $body): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_').'.csv';
        file_put_contents($path, $body);

        return new UploadedFile($path, 'agents.csv', 'text/csv', null, true);
    }

    public function test_admin_can_bulk_create_agents_from_csv(): void
    {
        $csv = "email,name,ticket_scope\n"
             . "alpha@example.test,Alpha Agent,1\n"
             . "bravo@example.test,Bravo Agent,2\n";

        $res = $this->actingAs($this->admin())
            ->post('/api/v1/admin/agents/bulk', [
                'file' => $this->csvFile($csv),
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.upserted', 2);
        $res->assertJsonPath('data.failed', 0);

        $alpha = Agent::where('email', 'alpha@example.test')->first();
        $bravo = Agent::where('email', 'bravo@example.test')->first();
        $this->assertNotNull($alpha);
        $this->assertNotNull($bravo);
        $this->assertSame('Alpha Agent', $alpha->name);
        $this->assertSame(2, (int) $bravo->ticket_scope);
        $this->assertSame('support_agent', $alpha->type);
    }

    public function test_bulk_create_updates_existing_agent_by_email(): void
    {
        $this->createAgent(['email' => 'existing@example.test', 'name' => 'Old Name', 'ticket_scope' => 1]);

        $csv = "email,name,ticket_scope\nexisting@example.test,New Name,3\n";

        $this->actingAs($this->admin())
            ->post('/api/v1/admin/agents/bulk', ['file' => $this->csvFile($csv)])
            ->assertOk();

        $agent = Agent::where('email', 'existing@example.test')->first();
        $this->assertSame('New Name', $agent->name);
        $this->assertSame(3, (int) $agent->ticket_scope);
        $this->assertSame(1, Agent::where('email', 'existing@example.test')->count());
    }

    public function test_bulk_create_skips_rows_missing_email(): void
    {
        $csv = "email,name\n,No Email\nvalid@example.test,Valid Agent\n";

        $res = $this->actingAs($this->admin())
            ->post('/api/v1/admin/agents/bulk', ['file' => $this->csvFile($csv)]);

        $res->assertOk();
        $res->assertJsonPath('data.upserted', 1);
        $res->assertJsonPath('data.failed', 1);
        $this->assertTrue(Agent::where('email', 'valid@example.test')->exists());
    }

    public function test_bulk_create_requires_file(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/agents/bulk', []);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['file']);
    }

    public function test_manager_cannot_bulk_create(): void
    {
        $csv = "email\nmgr@example.test\n";

        $res = $this->actingAs($this->manager())
            ->post('/api/v1/admin/agents/bulk', ['file' => $this->csvFile($csv)]);

        $res->assertForbidden();
    }

    public function test_customer_cannot_bulk_create(): void
    {
        $csv = "email\ncust@example.test\n";

        $res = $this->actingAs($this->customer())
            ->post('/api/v1/admin/agents/bulk', ['file' => $this->csvFile($csv)]);

        $res->assertForbidden();
    }

    public function test_unauthenticated_bulk_create_is_rejected(): void
    {
        $csv = "email\nanon@example.test\n";

        $res = $this->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/admin/agents/bulk', ['file' => $this->csvFile($csv)]);

        $res->assertUnauthorized();
    }
}
