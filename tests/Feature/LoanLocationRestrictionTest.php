<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\BusinessLocation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Superadmin\Entities\ModulePermissionLocation;

class LoanLocationRestrictionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_loan_module_access_is_restricted_when_location_is_not_enabled()
    {
        $user = User::where('username', 'lashini')->first();
        if (!$user) {
            $user = User::first();
        }
        $businessId = $user->business_id;

        // Ensure we have at least one location
        $location = BusinessLocation::where('business_id', $businessId)->first();

        // 1. Remove loan_module permission for this location in DB
        ModulePermissionLocation::updateOrCreate(
            ['business_id' => $businessId, 'module_name' => 'loan_module'],
            ['locations' => []]
        );

        // 2. Act as the user, set session data, and try to access the Loan index page
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId,
                'user.current_location' => $location->id,
            ])
            ->get('/loan');

        // 3. Assert response is forbidden
        $response->assertStatus(403);
    }

    public function test_loan_module_access_is_allowed_when_location_is_enabled()
    {
        $user = User::where('username', 'lashini')->first();
        if (!$user) {
            $user = User::first();
        }
        $businessId = $user->business_id;

        // Ensure we have at least one location
        $location = BusinessLocation::where('business_id', $businessId)->first();

        // 1. Allow loan_module permission for this location in DB
        ModulePermissionLocation::updateOrCreate(
            ['business_id' => $businessId, 'module_name' => 'loan_module'],
            ['locations' => [$location->id => 1]]
        );

        // 2. Act as the user, set session data, and try to access the Loan index page
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId,
                'user.current_location' => $location->id,
            ])
            ->get('/loan');

        // 3. Assert response is OK
        $response->assertStatus(200);
    }
}
