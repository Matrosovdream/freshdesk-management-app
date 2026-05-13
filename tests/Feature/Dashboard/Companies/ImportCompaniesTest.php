<?php

namespace Tests\Feature\Dashboard\Companies;

use App\Models\Company;
use Illuminate\Http\UploadedFile;

class ImportCompaniesTest extends CompanyTestCase
{
    private function csvFile(string $body): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_').'.csv';
        file_put_contents($path, $body);

        return new UploadedFile($path, 'companies.csv', 'text/csv', null, true);
    }

    public function test_admin_can_import_companies_from_csv(): void
    {
        $csv = "name,description,domains,industry,account_tier,health_score\n"
             . "Imported Alpha,First,alpha.test,Retail,standard,healthy\n"
             . "Imported Bravo,Second,\"bravo.test, bravo-co.test\",SaaS,premium,at-risk\n";

        $res = $this->actingAs($this->admin())
            ->post('/api/v1/admin/companies/import', [
                'file' => $this->csvFile($csv),
            ]);

        $res->assertOk();
        $res->assertJsonPath('data.upserted', 2);
        $res->assertJsonPath('data.failed', 0);

        $alpha = Company::where('name', 'Imported Alpha')->first();
        $bravo = Company::where('name', 'Imported Bravo')->first();
        $this->assertNotNull($alpha);
        $this->assertNotNull($bravo);
        $this->assertSame('Retail', $alpha->industry);
        $this->assertSame(['bravo.test', 'bravo-co.test'], $bravo->domains);
    }

    public function test_import_updates_existing_company_by_name(): void
    {
        $this->createCompany(['name' => 'Existing Co', 'industry' => 'Old']);

        $csv = "name,industry\nExisting Co,New Industry\n";

        $this->actingAs($this->admin())
            ->post('/api/v1/admin/companies/import', ['file' => $this->csvFile($csv)])
            ->assertOk();

        $this->assertSame('New Industry', Company::where('name', 'Existing Co')->value('industry'));
        $this->assertSame(1, Company::where('name', 'Existing Co')->count());
    }

    public function test_import_skips_rows_missing_name(): void
    {
        $csv = "name,industry\n,Tech\nValid Co,SaaS\n";

        $res = $this->actingAs($this->admin())
            ->post('/api/v1/admin/companies/import', ['file' => $this->csvFile($csv)]);

        $res->assertOk();
        $res->assertJsonPath('data.upserted', 1);
        $res->assertJsonPath('data.failed', 1);
        $this->assertTrue(Company::where('name', 'Valid Co')->exists());
    }

    public function test_import_requires_file(): void
    {
        $res = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/companies/import', []);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['file']);
    }

    public function test_manager_cannot_import(): void
    {
        $csv = "name\nManager Try\n";

        $res = $this->actingAs($this->manager())
            ->post('/api/v1/admin/companies/import', ['file' => $this->csvFile($csv)]);

        $res->assertForbidden();
    }

    public function test_customer_cannot_import(): void
    {
        $csv = "name\nCustomer Try\n";

        $res = $this->actingAs($this->customer())
            ->post('/api/v1/admin/companies/import', ['file' => $this->csvFile($csv)]);

        $res->assertForbidden();
    }

    public function test_unauthenticated_import_is_rejected(): void
    {
        $csv = "name\nAnon Try\n";

        $res = $this->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/admin/companies/import', ['file' => $this->csvFile($csv)]);

        $res->assertUnauthorized();
    }
}
