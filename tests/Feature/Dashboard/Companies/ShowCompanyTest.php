<?php

namespace Tests\Feature\Dashboard\Companies;

class ShowCompanyTest extends CompanyTestCase
{
    public function test_admin_can_view_a_company(): void
    {
        $company = $this->createCompany([
            'name'         => 'Showme Corp',
            'industry'     => 'Retail',
            'account_tier' => 'premium',
        ]);

        $res = $this->actingAs($this->admin())
            ->getJson('/api/v1/admin/companies/'.$company->id);

        $res->assertOk();
        $res->assertJsonPath('data.id', $company->id);
        $res->assertJsonPath('data.name', 'Showme Corp');
        $res->assertJsonPath('data.industry', 'Retail');
        $res->assertJsonPath('data.account_tier', 'premium');
    }

    public function test_show_returns_404_for_unknown_company(): void
    {
        $res = $this->actingAs($this->admin())
            ->getJson('/api/v1/admin/companies/999999');

        $res->assertNotFound();
    }

    public function test_show_returns_soft_deleted_company(): void
    {
        $company = $this->createCompany(['name' => 'Soft Deleted']);
        $company->delete();

        $res = $this->actingAs($this->admin())
            ->getJson('/api/v1/admin/companies/'.$company->id);

        $res->assertOk();
        $res->assertJsonPath('data.id', $company->id);
        $res->assertJsonPath('data.name', 'Soft Deleted');
    }

    public function test_unauthenticated_show_is_rejected(): void
    {
        $company = $this->createCompany();

        $res = $this->getJson('/api/v1/admin/companies/'.$company->id);

        $res->assertUnauthorized();
    }

    public function test_customer_role_cannot_view_company(): void
    {
        $company = $this->createCompany();

        $res = $this->actingAs($this->customer())
            ->getJson('/api/v1/admin/companies/'.$company->id);

        $res->assertForbidden();
    }
}
