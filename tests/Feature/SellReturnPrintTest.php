<?php

namespace Tests\Feature;

use App\User;
use App\Business;
use App\BusinessLocation;
use App\Transaction;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class SellReturnPrintTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sell_return_store_always_forces_receipt_enabled(): void
    {
        $this->withoutExceptionHandling();
        $user = User::find(2);
        if (!$user) {
            $this->markTestSkipped('No user found to run test.');
        }

        $businessId = $user->business_id;

        // 1. Ensure business location has print_receipt_on_invoice = 0
        $location = BusinessLocation::where('business_id', $businessId)->first();
        if (!$location) {
            $location = BusinessLocation::create([
                'business_id' => $businessId,
                'name' => 'Test Location',
                'location_id' => 'TL01',
                'print_receipt_on_invoice' => 0
            ]);
        } else {
            $location->print_receipt_on_invoice = 0;
            $location->save();
        }

        // 2. Create parent sell transaction
        $sell = Transaction::create([
            'business_id' => $businessId,
            'location_id' => $location->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'paid',
            'transaction_date' => '2026-05-26 10:30:00',
            'total_before_tax' => 120.0,
            'final_total' => 120.0,
            'invoice_no' => 'INV-TEST-SR-1',
            'created_by' => $user->id,
            'contact_id' => 1
        ]);

        // Create variation if needed, or get existing
        $variation = DB::table('variations')->first();
        $variationId = $variation ? $variation->id : 1;
        $productId = $variation ? $variation->product_id : 1;

        // Create parent sell line
        $sellLineId = DB::table('transaction_sell_lines')->insertGetId([
            'transaction_id' => $sell->id,
            'product_id' => $productId,
            'variation_id' => $variationId,
            'quantity' => 2,
            'unit_price' => 60.0,
            'unit_price_inc_tax' => 60.0,
        ]);

        $data = [
            'transaction_id' => $sell->id,
            'invoice_no' => 'RET-TEST-SR-1',
            'transaction_date' => '2026-05-26',
            'discount_type' => 'fixed',
            'discount_amount' => '0',
            'tax_id' => null,
            'products' => [
                [
                    'sell_line_id' => $sellLineId,
                    'quantity' => 1,
                    'unit_price_inc_tax' => 60.0
                ]
            ]
        ];

        // 3. Post to store sell return
        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $businessId,
                'user.id' => $user->id,
                'business' => Business::find($businessId),
            ])
            ->post('/sell-return', $data);

        $response->assertStatus(200);
        $result = $response->json();

        $this->assertEquals(1, $result['success']);
        $this->assertArrayHasKey('receipt', $result);
        $this->assertArrayHasKey('parent_sale_id', $result);
        $this->assertIsInt($result['parent_sale_id']);
        $this->assertEquals($sell->id, $result['parent_sale_id']);
        
        // Assert that receipt printing is always enabled/forced
        $this->assertTrue($result['receipt']['is_enabled'], 'Receipt printing is expected to be enabled/forced for sell returns.');
    }

    public function test_sell_return_add_and_store_for_dis_invoice_syncs_location_and_sell_lines(): void
    {
        $this->withoutExceptionHandling();
        $user = User::find(2);
        if (!$user) {
            $this->markTestSkipped('No user found to run test.');
        }

        $businessId = $user->business_id;

        // Ensure location exists for the business
        $location = BusinessLocation::where('business_id', $businessId)->first();
        if (!$location) {
            $location = BusinessLocation::create([
                'business_id' => $businessId,
                'name' => 'Test Location',
                'location_id' => 'TL01',
                'print_receipt_on_invoice' => 0
            ]);
        }

        // 1. Create a parent sell transaction of sub_type dis_invoice with null location_id
        $sell = Transaction::create([
            'business_id' => $businessId,
            'location_id' => null,
            'type' => 'sell',
            'sub_type' => 'dis_invoice',
            'status' => 'final',
            'payment_status' => 'paid',
            'transaction_date' => '2026-05-26 10:30:00',
            'total_before_tax' => 120.0,
            'final_total' => 120.0,
            'invoice_no' => 'INV-DIS-TEST-1',
            'created_by' => $user->id,
            'contact_id' => 1
        ]);

        // Get variation
        $variation = DB::table('variations')->first();
        $variationId = $variation ? $variation->id : 1;
        $productId = $variation ? $variation->product_id : 1;

        // 2. Create distribution invoice matching INV-DIS-TEST-1
        $distInvoiceId = DB::table('distribution_invoices')->insertGetId([
            'business_id' => $businessId,
            'customer_id' => 1,
            'invoice_no' => 'INV-DIS-TEST-1',
            'date' => '2026-05-26',
            'grand_total' => 120.0,
            'total' => 120.0,
            'status' => 'active',
            'added_by' => $user->id,
            'updated_by' => $user->id
        ]);

        // Create distribution invoice line
        DB::table('distribution_invoice_lines')->insert([
            'invoice_id' => $distInvoiceId,
            'product_id' => $productId,
            'unit_id' => 1,
            'qty' => 2,
            'unit_price' => 60.0,
            'amount' => 120.0,
            'discount' => 0,
            'final_amount' => 120.0
        ]);

        // Assert that currently no transaction sell lines exist for this transaction
        $this->assertEquals(0, DB::table('transaction_sell_lines')->where('transaction_id', $sell->id)->count());

        // 3. Make GET request to add sell return page
        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $businessId,
                'user.id' => $user->id,
                'business' => Business::find($businessId),
            ])
            ->get("/sell-return/add/{$sell->id}");

        $response->assertStatus(200);

        // Assert that the transaction_sell_lines were synced
        $sellLines = DB::table('transaction_sell_lines')->where('transaction_id', $sell->id)->get();
        $this->assertCount(1, $sellLines);
        $this->assertEquals(2, $sellLines->first()->quantity);
        $this->assertEquals(60.0, $sellLines->first()->unit_price);

        // Assert that location_id was populated
        $updatedSell = Transaction::find($sell->id);
        $this->assertEquals($location->id, $updatedSell->location_id);
    }

    public function test_sell_return_preview_route_renders_preview_html(): void
    {
        $this->withoutExceptionHandling();
        $user = User::find(2);
        if (!$user) {
            $this->markTestSkipped('No user found to run test.');
        }

        $businessId = $user->business_id;

        $location = BusinessLocation::where('business_id', $businessId)->first();

        // Create parent sell transaction
        $sell = Transaction::create([
            'business_id' => $businessId,
            'location_id' => $location->id,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'paid',
            'transaction_date' => '2026-05-26 10:30:00',
            'total_before_tax' => 120.0,
            'final_total' => 120.0,
            'invoice_no' => 'INV-PREV-1',
            'created_by' => $user->id,
            'contact_id' => 1
        ]);

        $variation = DB::table('variations')->first();
        $variationId = $variation ? $variation->id : 1;
        $productId = $variation ? $variation->product_id : 1;

        $sellLineId = DB::table('transaction_sell_lines')->insertGetId([
            'transaction_id' => $sell->id,
            'product_id' => $productId,
            'variation_id' => $variationId,
            'quantity' => 2,
            'unit_price' => 60.0,
            'unit_price_inc_tax' => 60.0,
        ]);

        $data = [
            'transaction_id' => $sell->id,
            'invoice_no' => 'RET-PREV-1',
            'transaction_date' => '2026-05-26',
            'discount_type' => 'fixed',
            'discount_amount' => '0',
            'tax_id' => null,
            'products' => [
                [
                    'sell_line_id' => $sellLineId,
                    'quantity' => 1,
                    'unit_price_inc_tax' => 60.0
                ]
            ]
        ];

        // Post to preview
        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $businessId,
                'user.id' => $user->id,
                'business' => Business::find($businessId),
            ])
            ->post('/sell-return/preview', $data);

        $response->assertStatus(200);
        $result = $response->json();

        $this->assertEquals(1, $result['success']);
        $this->assertStringContainsString('modal-dialog', $result['html']);
        $this->assertStringContainsString('RET-PREV-1', $result['html']);
        $this->assertStringContainsString('submit_sell_return_form_confirmed', $result['html']);
    }
}

