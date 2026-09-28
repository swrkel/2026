<?php

namespace Tests\Feature;

use App\Business;
use App\User;
use App\Contact;
use App\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Distribution\Entities\DistributionSalesOrder;
use Tests\TestCase;

class SalesOrderAddProductJQueryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure there is a business and user
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

    public function test_create_and_edit_views_have_correct_product_name_jquery_selector(): void
    {
        $projectRoot = base_path();
        
        $createPath = $projectRoot . '/Modules/Distribution/Resources/views/sales_orders/create.blade.php';
        $editPath = $projectRoot . '/Modules/Distribution/Resources/views/sales_orders/edit.blade.php';

        $this->assertFileExists($createPath);
        $this->assertFileExists($editPath);

        $createContents = file_get_contents($createPath);
        $editContents = file_get_contents($editPath);

        $this->assertIsString($createContents);
        $this->assertIsString($editContents);

        // Ensure we don't have the bug
        $this->assertStringNotContainsString('$(\'#search_product find(":selected")\')', $createContents);
        $this->assertStringNotContainsString('$(\'#search_product find(":selected")\')', $editContents);

        // Ensure we have the correct find syntax
        $this->assertStringContainsString('$(\'#search_product\').find(":selected")', $createContents);
        $this->assertStringContainsString('$(\'#search_product\').find(":selected")', $editContents);
    }

    public function test_store_and_update_sales_order_success_without_optional_arrays(): void
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();

        // Seed a customer contact
        $customer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'SO Customer',
            'mobile' => '12345678',
        ]);

        // Seed a product
        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'SO Product',
            'type' => 'single',
            'sku' => 'SO-PROD-01',
            'unit_id' => 1,
            'created_by' => $user->id,
        ]);

        // Create a sales order first
        $salesOrder = DistributionSalesOrder::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_contact' => $customer->mobile,
            'customer_address' => $customer->landmark,
            'date' => date('Y-m-d H:i:s'),
            'sales_order_no' => 'SO-0001',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        // Test update via PUT route
        $data = [
            'customer_id' => $customer->id,
            'date' => date('Y-m-d\TH:i'),
            'product_id' => [$product->id],
            'qty' => [3],
            'unit_price' => [50],
            'discount' => [0],
            'discount_type' => ['fixed'],
        ];

        $session = [
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ];

        $response = $this->actingAs($user)
            ->withSession($session)
            ->put("/distribution/sales-orders/{$salesOrder->id}", $data);

        $response->assertRedirect(route('distribution.sales_orders.index'));
        $response->assertSessionHas('status', 'Sales order updated successfully.');
    }
}
