<?php

namespace Tests\Feature;

use App\Business;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerStatementViewModalTest extends TestCase
{
    use DatabaseTransactions;

    public function testIndexViewContainsModalVisibilityFix()
    {
        $business = Business::first() ?? Business::create([
            'name' => 'Test Business',
            'currency_id' => 1,
            'owner_id' => 1,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2'
        ]);
        
        $user = User::where('business_id', $business->id)->first() ?? User::create([
            'first_name' => 'Tester',
            'last_name' => 'User',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'business_id' => $business->id
        ]);

        $this->actingAs($user);
        session()->put('business.id', $business->id);
        session()->put('user.business_id', $business->id);

        $response = $this->get('/customer-statement');
        $response->assertStatus(200);

        // Assert that the script to hide the Reference No in the modal exists
        $response->assertSee('shown.bs.modal');
        $response->assertSee('customer_statement_modal');
        $response->assertSee('cs_visible_cols');
        $response->assertSee('reference');
    }
}
