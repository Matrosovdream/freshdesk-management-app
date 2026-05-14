<?php

namespace Tests\Feature\Dashboard\Companies;

class ListCompaniesTest extends CompanyTestCase
{
    public function test_admin_gets_paginated_list_with_meta_envelope(): void
    {
        $this->createCompany(['name' => 'Alpha Co']);
        $this->createCompany(['name' => 'Beta Co']);
        $this->createCompany(['name' => 'Gamma Co']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.companies.index'));

        $res->assertOk();
        $res->assertJsonStructure([
            'data' => [
                'data' => [['id', 'name', 'industry', 'account_tier', 'health_score', 'open_tickets_count']],
                'meta' => ['total', 'next_cursor', 'per_page'],
            ],
        ]);
        $res->assertJsonPath('data.meta.total', 3);
    }

    public function test_returns_empty_list_when_no_companies_exist(): void
    {
        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.companies.index'));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 0);
        $res->assertJsonCount(0, 'data.data');
    }

    public function test_filter_by_industry_is_case_insensitive(): void
    {
        $this->createCompany(['name' => 'Tech One', 'industry' => 'Technology']);
        $this->createCompany(['name' => 'Tech Two', 'industry' => 'Technology']);
        $this->createCompany(['name' => 'Finance One', 'industry' => 'Finance']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.companies.index', ['industry' => 'technology']));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 2);
    }

    public function test_filter_by_account_tier(): void
    {
        $this->createCompany(['name' => 'Premium A', 'account_tier' => 'premium']);
        $this->createCompany(['name' => 'Premium B', 'account_tier' => 'premium']);
        $this->createCompany(['name' => 'Standard',  'account_tier' => 'standard']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.companies.index', ['account_tier' => 'premium']));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 2);
    }

    public function test_filter_by_domain_matches_json_column(): void
    {
        $this->createCompany(['name' => 'Domain Co',   'domains' => ['acme.test', 'acme-corp.test']]);
        $this->createCompany(['name' => 'Other Co',    'domains' => ['other.test']]);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.companies.index', ['domain' => 'acme.test']));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 1);
        $res->assertJsonPath('data.data.0.name', 'Domain Co');
    }

    public function test_search_matches_name_case_insensitive(): void
    {
        $this->createCompany(['name' => 'Acme Corp']);
        $this->createCompany(['name' => 'ACME Industries']);
        $this->createCompany(['name' => 'Globex']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.companies.index', ['search' => 'acme']));

        $res->assertOk();
        $res->assertJsonPath('data.meta.total', 2);
    }

    public function test_sort_by_name_default_is_descending(): void
    {
        $a = $this->createCompany(['name' => 'Alpha']);
        $b = $this->createCompany(['name' => 'Bravo']);
        $c = $this->createCompany(['name' => 'Charlie']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.companies.index', ['sort' => 'name']));

        $res->assertOk();
        $res->assertJsonPath('data.data.0.id', $c->id);
        $res->assertJsonPath('data.data.1.id', $b->id);
        $res->assertJsonPath('data.data.2.id', $a->id);
    }

    public function test_sort_by_name_ascending(): void
    {
        $a = $this->createCompany(['name' => 'Alpha']);
        $b = $this->createCompany(['name' => 'Bravo']);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.companies.index', ['sort' => '+name']));

        $res->assertOk();
        $res->assertJsonPath('data.data.0.id', $a->id);
        $res->assertJsonPath('data.data.1.id', $b->id);
    }

    public function test_open_tickets_count_reflects_open_statuses(): void
    {
        $company = $this->createCompany(['name' => 'Counted Co']);

        $contact = \App\Models\Contact::create([
            'freshdesk_id' => random_int(100000, 999999999),
            'name'         => 'Tester',
            'email'        => 'tester_'.uniqid().'@example.test',
            'company_id'   => $company->id,
        ]);

        \App\Models\Ticket::create([
            'freshdesk_id' => random_int(100000, 999999999),
            'subject'      => 'Open',
            'status'       => 2,
            'priority'     => 1,
            'company_id'   => $company->id,
            'requester_id' => $contact->id,
        ]);
        \App\Models\Ticket::create([
            'freshdesk_id' => random_int(100000, 999999999),
            'subject'      => 'Pending',
            'status'       => 3,
            'priority'     => 1,
            'company_id'   => $company->id,
            'requester_id' => $contact->id,
        ]);
        \App\Models\Ticket::create([
            'freshdesk_id' => random_int(100000, 999999999),
            'subject'      => 'Resolved',
            'status'       => 4,
            'priority'     => 1,
            'company_id'   => $company->id,
            'requester_id' => $contact->id,
        ]);

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.companies.index', ['search' => 'Counted']));

        $res->assertOk();
        $res->assertJsonPath('data.data.0.open_tickets_count', 2);
    }

    public function test_per_page_limits_results(): void
    {
        foreach (range(1, 5) as $i) {
            $this->createCompany(['name' => "Company $i"]);
        }

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.companies.index', ['per_page' => 2]));

        $res->assertOk();
        $res->assertJsonCount(2, 'data.data');
        $res->assertJsonPath('data.meta.per_page', 2);
        $res->assertJsonPath('data.meta.total', 5);
        $res->assertJsonPath('data.meta.next_cursor', 2);
    }

    public function test_cursor_returns_next_page(): void
    {
        foreach (range(1, 5) as $i) {
            $this->createCompany(['name' => "Co $i"]);
        }

        $res = $this->actingAs($this->admin())
            ->getJson(route('api.admin.companies.index', ['per_page' => 2, 'cursor' => 2, 'sort' => '+name']));

        $res->assertOk();
        $res->assertJsonCount(2, 'data.data');
        $res->assertJsonPath('data.meta.next_cursor', 4);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $res = $this->getJson(route('api.admin.companies.index'));

        $res->assertUnauthorized();
    }

    public function test_customer_role_cannot_access_admin_list(): void
    {
        $res = $this->actingAs($this->customer())
            ->getJson(route('api.admin.companies.index'));

        $res->assertForbidden();
    }
}
