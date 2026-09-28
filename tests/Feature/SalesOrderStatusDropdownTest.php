<?php

namespace Tests\Feature;

use App\Business;
use App\User;
use App\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Distribution\Entities\DistributionSalesOrder;
use Tests\TestCase;

class SalesOrderStatusDropdownTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup default business and user if not exists
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

    public function test_sales_order_status_update_route_works(): void
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();
        $customer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'Test Customer',
        ]);

        $salesOrder = DistributionSalesOrder::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'date' => date('Y-m-d H:i:s'),
            'sales_order_no' => 'SO-TEST-123',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $session = [
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ];

        $response = $this->actingAs($user)
            ->withSession($session)
            ->post("/distribution/sales-orders/{$salesOrder->id}/status", [
                'status' => 'inactive'
            ]);

        $response->assertRedirect();
        $this->assertEquals('inactive', $salesOrder->fresh()->status);
    }

    public function test_sales_order_datatable_status_column_has_dropdown_html(): void
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();
        $customer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'Test Customer',
        ]);

        $salesOrder = DistributionSalesOrder::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'date' => date('Y-m-d H:i:s'),
            'sales_order_no' => 'SO-TEST-124',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $session = [
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ];

        $response = $this->actingAs($user)
            ->withSession($session)
            ->get("/distribution/sales-orders", ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $data = $response->json();
        
        // Find the specific row we inserted
        $targetRow = null;
        foreach ($data['data'] as $row) {
            if ($row['sales_order_no'] === 'SO-TEST-124') {
                $targetRow = $row;
                break;
            }
        }
        
        $this->assertNotNull($targetRow);
        $this->assertStringContainsString('change-status-btn', $targetRow['status']);
        $this->assertStringContainsString('so-status-form', $targetRow['status']);
        $this->assertStringContainsString('Active', $targetRow['status']);
    }
}
