<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\BusinessLocation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Superadmin\Entities\ModulePermissionLocation;

class LoanContactTypeVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    private function getAuthenticatedUser()
    {
        $user = User::where('username', 'lashini')->first();
        if (!$user) {
            $user = User::create([
                'surname' => 'Mr',
                'first_name' => 'Lashini',
                'email' => 'lashini@test.com',
                'username' => 'lashini',
                'password' => bcrypt('password'),
                'business_id' => 1
            ]);
        }
        
        $businessId = $user->business_id;

        // Ensure at least one business location is associated and enabled for loan_module
        $location = BusinessLocation::where('business_id', $businessId)->first();
        if (!$location) {
            $location = BusinessLocation::create([
                'business_id' => $businessId,
                'name' => 'Test Location',
                'location_id' => 'LOC-001'
            ]);
        }

        ModulePermissionLocation::updateOrCreate(
            ['business_id' => $businessId, 'module_name' => 'loan_module'],
            ['locations' => [$location->id => 1]]
        );

        return $user;
    }

    public function test_contact_type_dropdown_is_visible_when_permission_enabled()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;
        $location = BusinessLocation::where('business_id', $businessId)->first();

        // Mock current subscription package_details containing loan_show_contact_type = 1
        \Modules\Superadmin\Entities\Subscription::updateOrCreate(
            ['business_id' => $businessId, 'status' => 'approved'],
            [
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+1 year')),
                'package_details' => ['loan_show_contact_type' => 1]
            ]
        );

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId,
                'user.current_location' => $location->id
            ])
            ->get('/loan/create');

        $response->assertStatus(200);
        $html = $response->getContent();
        
        $this->assertStringContainsString('name="contact_type"', $html);
        $this->assertMatchesRegularExpression('/class="col-md-3"\s*>\s*<label for="contact_id"/i', $html);
    }

    public function test_contact_type_dropdown_is_hidden_and_layout_adjusted_when_permission_disabled()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;
        $location = BusinessLocation::where('business_id', $businessId)->first();

        // Mock current subscription package_details containing loan_show_contact_type = 0
        \Modules\Superadmin\Entities\Subscription::updateOrCreate(
            ['business_id' => $businessId, 'status' => 'approved'],
            [
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+1 year')),
                'package_details' => ['loan_show_contact_type' => 0]
            ]
        );

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId,
                'user.current_location' => $location->id
            ])
            ->get('/loan/create');

        $response->assertStatus(200);
        $html = $response->getContent();

        // Dropdown should be replaced by hidden input
        $this->assertStringContainsString('type="hidden" name="contact_type"', $html);
        // Grid classes for remaining columns should adapt to col-md-4
        $this->assertMatchesRegularExpression('/class="col-md-4"\s*>\s*<label for="contact_id"/i', $html);
    }
}
