<?php

namespace Tests\Feature\Dashboard\Companies;

use App\Models\AuditLog;
use App\Models\Company;

class CreateCompanyTest extends CompanyTestCase
{
    public function test_admin_can_create_a_company(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.companies.store'), [
                'name'         => 'Newco Inc',
                'description'  => 'A brand new account',
                'domains'      => ['newco.test'],
                'industry'     => 'SaaS',
                'account_tier' => 'premium',
                'health_score' => 'healthy',
                'renewal_date' => '2027-01-15',
                'note'         => 'Onboarded by sales',
                'custom_fields'=> ['region' => 'EMEA'],
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.name', 'Newco Inc');
        $res->assertJsonPath('data.industry', 'SaaS');
        $res->assertJsonPath('data.account_tier', 'premium');

        $this->assertDatabaseHas('companies', ['name' => 'Newco Inc', 'industry' => 'SaaS']);
    }

    public function test_create_assigns_freshdesk_id_above_existing_max(): void
    {
        $this->createCompany(['freshdesk_id' => 5_000_000]);

        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.companies.store'), ['name' => 'Auto FD ID Co']);

        $res->assertOk();
        $this->assertSame(5_000_001, (int) Company::where('name', 'Auto FD ID Co')->value('freshdesk_id'));
    }

    public function test_create_uses_seed_freshdesk_id_when_table_empty(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.companies.store'), ['name' => 'First Co']);

        $res->assertOk();
        $this->assertSame(1_000_000, (int) Company::where('name', 'First Co')->value('freshdesk_id'));
    }

    public function test_create_writes_audit_log(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('api.admin.companies.store'), ['name' => 'Audited Co'])
            ->assertOk();

        $log = AuditLog::where('action', 'company.created')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('Company', $log->target_type);
    }

    public function test_create_requires_name(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.companies.store'), ['industry' => 'Tech']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['name']);
    }

    public function test_create_rejects_duplicate_name(): void
    {
        $this->createCompany(['name' => 'Dup Co']);

        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.companies.store'), ['name' => 'Dup Co']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['name']);
    }

    public function test_create_rejects_invalid_renewal_date(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.companies.store'), [
                'name'         => 'Bad Date Co',
                'renewal_date' => 'not-a-date',
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['renewal_date']);
    }

    public function test_create_rejects_non_array_domains(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.companies.store'), [
                'name'    => 'Bad Domain Co',
                'domains' => 'acme.test',
            ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['domains']);
    }

    public function test_manager_cannot_create_company(): void
    {
        $res = $this->actingAs($this->manager())
            ->postJson(route('api.admin.companies.store'), ['name' => 'Manager Attempt']);

        $res->assertForbidden();
    }

    public function test_unauthenticated_create_is_rejected(): void
    {
        $res = $this->postJson(route('api.admin.companies.store'), ['name' => 'Anon Attempt']);

        $res->assertUnauthorized();
    }
}
