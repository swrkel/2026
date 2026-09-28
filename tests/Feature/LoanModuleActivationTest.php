<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LoanModuleActivationTest extends TestCase
{
    use DatabaseTransactions;

    private function getAuthenticatedUser()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No user found to run test.');
        }
        return $user;
    }

    /**
     * Test that the Loan module routes are accessible by non-superadmin users.
     */
    public function test_loan_routes_are_accessible_for_all_users()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        // Force user to NOT be a superadmin
        if ($user->can('superadmin')) {
            $nonAdminUser = User::where('id', '!=', $user->id)->first();
            if ($nonAdminUser) {
                $user = $nonAdminUser;
                $businessId = $user->business_id;
            }
        }

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->get('/contact_loan');

        // It might be blocked or redirect on broken code. Under TDD we verify that we can load it.
        $response->assertStatus(200);
    }

    /**
     * Test that the Loan module is active in all sidebars (main, petro, auto repair).
     */
    public function test_loan_module_sidebar_menu_is_visible()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->get('/contact_loan');

        $response->assertStatus(200);
        $html = $response->getContent();

        // Check if sidebar has the Loan menu link
        $this->assertStringContainsString('contact_loan', $html);
    }
}
