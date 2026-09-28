<?php

namespace Tests\Feature;

use App\Business;
use App\User;
use App\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Distribution\Entities\DistributionSalesOrder;
use Tests\TestCase;

class IS7955SalesOrderShowUrlModalTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (!Business::query()->exists()) {
            $currencyId = \Illuminate\Support\Facades\DB::table('currencies')->value('id');
            if (!$currencyId) {
                $currencyId = \Illuminate\Support\Facades\DB::table('currencies')->insertGetId([
                    'country' => 'Test',
                    'currency' => 'Test Rupee',
                    'code' => 'TST',
                    'symbol' => 'Rs',
                    'thousand_separator' => ',',
                    'decimal_separator' => '.',
                ]);
            }
            $userId = \Illuminate\Support\Facades\DB::table('users')->insertGetId([
                'first_name' => 'SO',
                'last_name' => 'Tester',
                'username' => 'so_tester_' . uniqid(),
                'email' => 'so_tester_' . uniqid() . '@example.test',
                'password' => bcrypt('password'),
                'language' => 'en',
                'status' => 'active',
            ]);
            $businessId = \Illuminate\Support\Facades\DB::table('business')->insertGetId([
                'name' => 'SO Test Business',
                'currency_id' => $currencyId,
                'owner_id' => $userId,
                'stop_selling_before' => 0,
                'auto_repair_settings' => '',
                'asset_settings' => '',
                'font_size' => 12,
                'font_family' => 'Arial',
                'weighing_scale_setting' => '',
                'currency_precision' => '2',
                'quantity_precision' => '2',
            ]);
            \Illuminate\Support\Facades\DB::table('users')->where('id', $userId)->update(['business_id' => $businessId]);
        }
    }

    public function test_show_sales_order_url_modal_endpoint(): void
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();

        // Seed customer contact
        $customer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'SO Customer',
            'mobile' => '12345678',
        ]);

        // Create a sales order
        $salesOrder = DistributionSalesOrder::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_contact' => $customer->mobile,
            'customer_address' => $customer->landmark,
            'date' => date('Y-m-d H:i:s'),
            'sales_order_no' => 'SO-9999',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $session = [
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ];

        // Call the show-url modal endpoint via AJAX
        $response = $this->actingAs($user)
            ->withSession($session)
            ->get("/distribution/sales-orders/{$salesOrder->id}/show-url", ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertSee('SO-9999');
        $response->assertSee('View Sales Order URL');
    }
}
