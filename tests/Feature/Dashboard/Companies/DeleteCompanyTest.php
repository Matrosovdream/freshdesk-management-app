<?php

namespace Tests\Feature\Dashboard\Companies;

use App\Models\AuditLog;
use App\Models\Company;

class DeleteCompanyTest extends CompanyTestCase
{
    public function test_admin_can_soft_delete_company(): void
    {
        $company = $this->createCompany(['name' => 'Delete Me']);

        $res = $this->actingAs($this->admin())
            ->deleteJson('/api/v1/admin/companies/'.$company->id);

        $res->assertOk();
        $res->assertJsonPath('data.id', $company->id);
        $res->assertJsonPath('data.deleted', true);

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
        $this->assertNotNull(Company::withTrashed()->find($company->id));
    }

    public function test_deleted_company_disappears_from_list(): void
    {
        $a = $this->createCompany(['name' => 'Keep']);
        $b = $this->createCompany(['name' => 'Drop']);

        $this->actingAs($this->admin())
            ->deleteJson('/api/v1/admin/companies/'.$b->id)
            ->assertOk();

        $res = $this->actingAs($this->admin())
            ->getJson('/api/v1/admin/companies');

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 1);
        $res->assertJsonPath('data.data.0.id', $a->id);
    }

    public function test_delete_writes_audit_log(): void
    {
        $company = $this->createCompany();

        $this->actingAs($this->admin())
            ->deleteJson('/api/v1/admin/companies/'.$company->id)
            ->assertOk();

        $log = AuditLog::where('action', 'company.deleted')
            ->where('target_id', $company->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Company', $log->target_type);
    }

    public function test_delete_returns_404_for_unknown_company(): void
    {
        $res = $this->actingAs($this->admin())
            ->deleteJson('/api/v1/admin/companies/999999');

        $res->assertNotFound();
    }

    public function test_manager_cannot_delete_company(): void
    {
        $company = $this->createCompany();

        $res = $this->actingAs($this->manager())
            ->deleteJson('/api/v1/admin/companies/'.$company->id);

        $res->assertForbidden();
    }

    public function test_customer_cannot_delete_company(): void
    {
        $company = $this->createCompany();

        $res = $this->actingAs($this->customer())
            ->deleteJson('/api/v1/admin/companies/'.$company->id);

        $res->assertForbidden();
    }

    public function test_unauthenticated_delete_is_rejected(): void
    {
        $company = $this->createCompany();

        $res = $this->deleteJson('/api/v1/admin/companies/'.$company->id);

        $res->assertUnauthorized();
    }
}
