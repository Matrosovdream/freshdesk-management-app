<?php

namespace Tests\Feature\Dashboard\Companies;

use Illuminate\Support\Facades\Storage;

class ExportCompaniesTest extends CompanyTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admin_can_export_and_csv_contains_rows(): void
    {
        $this->createCompany(['name' => 'Export Alpha', 'industry' => 'Retail']);
        $this->createCompany(['name' => 'Export Bravo', 'industry' => 'SaaS']);

        $res = $this->actingAs($this->admin())
            ->postJson(route('api.admin.companies.export'));

        $res->assertOk();
        $res->assertJsonStructure(['data' => ['download_url']]);

        $files = Storage::disk('public')->files('exports');
        $this->assertNotEmpty($files);

        $csv = Storage::disk('public')->get($files[0]);
        $this->assertStringContainsString('Export Alpha', $csv);
        $this->assertStringContainsString('Export Bravo', $csv);
        $this->assertStringContainsString('id,name,domains,industry', $csv);
    }

    public function test_manager_cannot_export(): void
    {
        $res = $this->actingAs($this->manager())
            ->postJson(route('api.admin.companies.export'));

        $res->assertForbidden();
    }

    public function test_customer_cannot_export(): void
    {
        $res = $this->actingAs($this->customer())
            ->postJson(route('api.admin.companies.export'));

        $res->assertForbidden();
    }

    public function test_unauthenticated_export_is_rejected(): void
    {
        $res = $this->postJson(route('api.admin.companies.export'));

        $res->assertUnauthorized();
    }
}
