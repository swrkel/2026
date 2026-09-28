<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Tenant;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CustomersBankTenantRouteTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Set tenant database prefix to empty so we can use the testing database name as tenant ID
        putenv("TENANT_DATABASE_PREFIX=");
    }

    protected function tearDown(): void
    {
        // Restore environment
        putenv("TENANT_DATABASE_PREFIX");
        parent::tearDown();
    }

    public function test_bank_customers_route_is_registered_in_tenant_routes()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No user found to run test.');
        }

        $activeDbName = config('database.connections.mysql.database');

        $tenant = Tenant::create([
            'id' => $activeDbName,
            'tenancy_db_name' => $activeDbName
        ]);
        $tenant->domains()->create(['domain' => 'tenant.localhost']);

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $user->business_id,
                'user.business_id' => $user->business_id
            ])
            ->get('http://tenant.localhost/bank-customers');

        $response->assertStatus(200);
    }
}
