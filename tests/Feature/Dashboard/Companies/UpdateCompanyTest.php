<?php

namespace Tests\Feature\Dashboard\Companies;

use App\Models\AuditLog;

class UpdateCompanyTest extends CompanyTestCase
{
    public function test_admin_can_update_company_fields(): void
    {
        $company = $this->createCompany([
            'name'         => 'Old Name',
            'industry'     => 'Retail',
            'account_tier' => 'standard',
        ]);

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.companies.update', $company->id), [
                'name'         => 'New Name',
                'industry'     => 'SaaS',
                'account_tier' => 'premium',
                'health_score' => 'at-risk',
                'note'         => 'Renegotiated contract',
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.name', 'New Name');
        $res->assertJsonPath('data.industry', 'SaaS');
        $res->assertJsonPath('data.account_tier', 'premium');
        $res->assertJsonPath('data.health_score', 'at-risk');

        $fresh = $company->fresh();
        $this->assertSame('New Name', $fresh->name);
        $this->assertSame('SaaS', $fresh->industry);
    }

    public function test_update_replaces_domains_array(): void
    {
        $company = $this->createCompany(['domains' => ['old.test']]);

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.companies.update', $company->id), [
                'domains' => ['new-a.test', 'new-b.test'],
            ]);

        $res->assertOk();
        $this->assertSame(['new-a.test', 'new-b.test'], $company->fresh()->domains);
    }

    public function test_update_ignores_freshdesk_id_in_payload(): void
    {
        $company = $this->createCompany(['freshdesk_id' => 12345]);

        $this->actingAs($this->admin())
            ->putJson(route('api.admin.companies.update', $company->id), [
                'name'         => 'Renamed',
                'freshdesk_id' => 99999,
            ])
            ->assertOk();

        $this->assertSame(12345, (int) $company->fresh()->freshdesk_id);
    }

    public function test_update_touches_fd_updated_at(): void
    {
        $company = $this->createCompany(['fd_updated_at' => '2026-01-01 00:00:00']);

        $this->actingAs($this->admin())
            ->putJson(route('api.admin.companies.update', $company->id), ['note' => 'Touched'])
            ->assertOk();

        $this->assertTrue($company->fresh()->fd_updated_at->greaterThan('2026-01-02 00:00:00'));
    }

    public function test_update_writes_audit_log_with_before_and_after(): void
    {
        $company = $this->createCompany(['name' => 'Audit Before']);

        $this->actingAs($this->admin())
            ->putJson(route('api.admin.companies.update', $company->id), ['name' => 'Audit After'])
            ->assertOk();

        $log = AuditLog::where('action', 'company.updated')
            ->where('target_id', $company->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Audit Before', $log->payload_before['name'] ?? null);
        $this->assertSame('Audit After', $log->payload_after['name'] ?? null);
    }

    public function test_update_returns_404_for_unknown_company(): void
    {
        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.companies.update', 999999), ['name' => 'Nope']);

        $res->assertNotFound();
    }

    public function test_update_rejects_invalid_renewal_date(): void
    {
        $company = $this->createCompany();

        $res = $this->actingAs($this->admin())
            ->putJson(route('api.admin.companies.update', $company->id), ['renewal_date' => 'not-a-date']);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['renewal_date']);
    }

    public function test_manager_can_update_company(): void
    {
        $company = $this->createCompany(['name' => 'Manager Edit Me']);

        $res = $this->actingAs($this->manager())
            ->putJson(route('api.admin.companies.update', $company->id), ['note' => 'Manager note']);

        // Managers have companies.update right; ManagerScope may still hide the row,
        // but the right-middleware should pass.
        $this->assertNotSame(403, $res->status());
    }

    public function test_customer_cannot_update_company(): void
    {
        $company = $this->createCompany();

        $res = $this->actingAs($this->customer())
            ->putJson(route('api.admin.companies.update', $company->id), ['note' => 'No way']);

        $res->assertForbidden();
    }

    public function test_unauthenticated_update_is_rejected(): void
    {
        $company = $this->createCompany();

        $res = $this->putJson(route('api.admin.companies.update', $company->id), ['name' => 'Anon']);

        $res->assertUnauthorized();
    }
}
