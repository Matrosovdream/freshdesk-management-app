<?php

namespace Tests\Feature\Dashboard\Companies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class CompanyTestCase extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function admin(): User
    {
        return User::where('email', 'admin@example.test')->firstOrFail();
    }

    protected function manager(): User
    {
        return User::where('email', 'manager@example.test')->firstOrFail();
    }

    protected function customer(): User
    {
        return User::where('email', 'customer@example.test')->firstOrFail();
    }

    protected function createCompany(array $overrides = []): Company
    {
        return Company::create(array_merge([
            'freshdesk_id'  => random_int(100000, 999999999),
            'name'          => 'Acme '.uniqid(),
            'industry'      => 'Technology',
            'account_tier'  => 'standard',
            'health_score'  => 'healthy',
            'domains'       => ['acme.test'],
            'fd_created_at' => now(),
            'fd_updated_at' => now(),
        ], $overrides));
    }
}
