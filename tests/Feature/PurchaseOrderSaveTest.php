<?php
 
 namespace Tests\Feature;
 
 use App\User;
 use App\Business;
 use App\Transaction;
 use Tests\TestCase;
 use Illuminate\Support\Facades\DB;

class PurchaseOrderSaveTest extends TestCase
{
    public function test_purchase_order_save_successfully()
    {
        $this->withoutExceptionHandling();
        $user = User::find(2);
        
        $data = [
            'is_purchase_order' => 1,
            'contact_id' => 11, // CPC Supplier
            'transaction_date' => '05/17/2026',
            'status' => 'ordered',
            'ref_no' => 'REF-TEST-12345',
            'location_id' => 2, // BL0002
            'exchange_rate' => 1,
            'total_before_tax' => '100',
            'final_total' => '100',
            'purchases' => [
                [
                    'product_id' => 1,
                    'variation_id' => 1,
                    'quantity' => 10,
                    'pp_without_discount' => 10,
                    'purchase_price' => 10,
                    'purchase_price_inc_tax' => 10,
                    'item_tax' => 0,
                    'purchase_line_tax_id' => null,
                ]
            ],
            'payment' => [],
        ];

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => 2,
                'user.id' => 2,
                'business' => Business::find(2),
            ])
            ->post('/purchases', $data);

        $response->assertStatus(302);
        
        $response->assertSessionHas('status');
        $status = session('status');
        $this->assertEquals(1, $status['success']);
        $this->assertEquals('Saved Successfully', $status['msg']);

        $this->assertDatabaseHas('transactions', [
            'ref_no' => 'REF-TEST-12345',
            'status' => 'ordered',
        ]);
    }

    public function test_add_purchase_order_page_renders_with_correct_is_purchase_order_value()
    {
        $user = User::find(2);

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => 2,
                'user.id' => 2,
                'business' => \App\Business::find(2),
            ])
            ->get('/purchases/add-purchase-order');

        $response->assertStatus(200);
        $response->assertSee('name="is_purchase_order" id="is_purchase_order" value="1"', false);
    }

    public function test_purchase_created_updates_parent_purchase_order_status()
    {
        $this->withoutExceptionHandling();
        $user = User::find(2);

        // Delete any leftover test data
        DB::table('transactions')->whereIn('invoice_no', ['APO-PO-999', 'REF-P-888'])->delete();

        // 1. Insert a test Purchase Order (status = ordered)
        $po_id = DB::table('transactions')->insertGetId([
            'business_id' => 2,
            'type' => 'purchase',
            'status' => 'ordered',
            'payment_status' => 'due',
            'invoice_no' => 'APO-PO-999',
            'ref_no' => 'REF-PO-999',
            'contact_id' => 11,
            'transaction_date' => '2026-05-17 12:00:00',
            'final_total' => 100.00,
            'created_by' => 2,
        ]);

        // 2. Post a Purchase linked to the PO, selecting status = pending
        $data = [
            'is_purchase_order' => 0,
            'linked_purchase_order_id' => $po_id,
            'contact_id' => 11,
            'transaction_date' => '05/17/2026',
            'invoice_date' => '05/17/2026',
            'store_id' => 1,
            'status' => 'pending',
            'ref_no' => 'REF-P-888',
            'location_id' => 2,
            'exchange_rate' => 1,
            'total_before_tax' => '100',
            'final_total' => '100',
            'purchases' => [
                [
                    'product_id' => 1,
                    'variation_id' => 1,
                    'quantity' => 10,
                    'pp_without_discount' => 10,
                    'purchase_price' => 10,
                    'purchase_price_inc_tax' => 10,
                    'item_tax' => 0,
                    'purchase_line_tax_id' => null,
                ]
            ],
            'payment' => [],
        ];

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => 2,
                'user.id' => 2,
                'business' => Business::find(2),
            ])
            ->post('/purchases', $data);

        $response->assertStatus(302);

        // 3. Fetch PO from DB and assert its status is now 'pending' (matching the purchase status selected)
        $parent_po = DB::table('transactions')->where('id', $po_id)->first();
        $this->assertEquals('pending', $parent_po->status, "The parent PO status should be updated to 'pending', matching the linked purchase.");

        // Clean up
        DB::table('transactions')->whereIn('invoice_no', ['APO-PO-999', 'REF-P-888'])->delete();
    }

    public function test_purchase_tax_update_to_none_saves_correctly()
    {
        $this->withoutExceptionHandling();
        $user = User::find(2);

        // 1. Create a purchase with tax_id = 1
        $data = [
            'is_purchase_order' => 0,
            'contact_id' => 11, // CPC Supplier
            'transaction_date' => '05/17/2026',
            'invoice_date' => '05/17/2026',
            'store_id' => 1,
            'status' => 'received',
            'ref_no' => 'REF-UPDATE-TAX-1',
            'location_id' => 2, // BL0002
            'exchange_rate' => 1,
            'total_before_tax' => '100',
            'final_total' => '118',
            'purchases' => [
                [
                    'product_id' => 1,
                    'variation_id' => 1,
                    'quantity' => 10,
                    'pp_without_discount' => 10,
                    'purchase_price' => 10,
                    'purchase_price_inc_tax' => 11.8,
                    'item_tax' => 1.8,
                    'purchase_line_tax_id' => 1,
                ]
            ],
            'payment' => [],
        ];

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => 2,
                'user.id' => 2,
                'business' => Business::find(2),
            ])
            ->post('/purchases', $data);

        $response->assertStatus(302);

        $transaction = Transaction::where('ref_no', 'REF-UPDATE-TAX-1')->first();
        $this->assertNotNull($transaction);
        $purchase_line = $transaction->purchase_lines->first();
        $this->assertEquals(1, $purchase_line->tax_id);

        // 2. Update the purchase changing tax to none (empty string)
        $updateData = [
            'is_purchase_order' => 0,
            'contact_id' => 11,
            'transaction_date' => '05/17/2026',
            'invoice_date' => '05/17/2026',
            'status' => 'received',
            'ref_no' => 'REF-UPDATE-TAX-1',
            'location_id' => 2,
            'exchange_rate' => 1,
            'total_before_tax' => '100',
            'final_total' => '100',
            'purchases' => [
                [
                    'purchase_line_id' => $purchase_line->id,
                    'product_id' => 1,
                    'variation_id' => 1,
                    'quantity' => 10,
                    'pp_without_discount' => 10,
                    'purchase_price' => 10,
                    'purchase_price_inc_tax' => 10,
                    'item_tax' => 0,
                    'purchase_line_tax_id' => '', // none
                ]
            ],
            'payment' => [],
        ];

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => 2,
                'user.id' => 2,
                'business' => Business::find(2),
            ])
            ->put('/purchases/' . $transaction->id, $updateData);

        $response->assertStatus(302);

        // Reload from DB
        $purchase_line->refresh();
        $this->assertNull($purchase_line->tax_id);

        // Clean up
        $transaction->delete();
    }

    public function test_update_purchase_when_sibling_purchase_shares_same_ref_no_succeeds()
    {
        $this->withoutExceptionHandling();
        $user     = User::find(2);
        $business = Business::find(2);

        // Cleanup leftovers
        DB::table('transactions')->where('ref_no', 'REF-SIBLING-TEST')->delete();

        // Two standard purchases sharing the same ref_no (simulates partial PO deliveries)
        $purchase1_id = DB::table('transactions')->insertGetId([
            'business_id'      => 2,
            'type'             => 'purchase',
            'status'           => 'pending',
            'payment_status'   => 'due',
            'invoice_no'       => 'APN-SIB-1',
            'ref_no'           => 'REF-SIBLING-TEST',
            'contact_id'       => 11,
            'transaction_date' => '2026-05-21 12:00:00',
            'final_total'      => 100.00,
            'created_by'       => 2,
        ]);

        $purchase2_id = DB::table('transactions')->insertGetId([
            'business_id'      => 2,
            'type'             => 'purchase',
            'status'           => 'pending',
            'payment_status'   => 'due',
            'invoice_no'       => 'APN-SIB-2',
            'ref_no'           => 'REF-SIBLING-TEST',
            'contact_id'       => 11,
            'transaction_date' => '2026-05-21 12:00:00',
            'final_total'      => 100.00,
            'created_by'       => 2,
        ]);

        $updateData = [
            'is_purchase_order'  => 0,
            'contact_id'         => 11,
            'transaction_date'   => '05/21/2026',
            'invoice_date'       => '05/21/2026',
            'status'             => 'pending',
            'ref_no'             => 'REF-SIBLING-TEST',
            'location_id'        => 2,
            'exchange_rate'      => 1,
            'total_before_tax'   => '100',
            'final_total'        => '100',
            'purchases'          => [
                [
                    'product_id'             => 1,
                    'variation_id'           => 1,
                    'quantity'               => 10,
                    'pp_without_discount'    => 10,
                    'purchase_price'         => 10,
                    'purchase_price_inc_tax' => 10,
                    'item_tax'               => 0,
                    'purchase_line_tax_id'   => null,
                ],
            ],
            'payment' => [],
        ];

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => 2,
                'user.id'          => 2,
                'business'         => $business,
            ])
            ->put('/purchases/' . $purchase2_id, $updateData);

        $response->assertStatus(302);
        $response->assertSessionHas('status');
        $status = session('status');
        $this->assertEquals(1, $status['success'], 'Editing a purchase should succeed when another purchase for the same contact shares the same ref_no.');

        // Cleanup
        DB::table('transactions')->whereIn('id', [$purchase1_id, $purchase2_id])->delete();
    }

    public function test_update_purchase_with_same_ref_no_as_its_purchase_order_succeeds()
    {
        $user = User::find(2);
        $business = Business::find(2);

        // Clean up any leftovers
        DB::table('transactions')->whereIn('ref_no', ['REF-TDD-TEST-999'])->delete();

        // 1. Create a Purchase Order (status = ordered)
        $po_id = DB::table('transactions')->insertGetId([
            'business_id' => 2,
            'type' => 'purchase',
            'status' => 'ordered',
            'payment_status' => 'due',
            'invoice_no' => 'APO-TDD-999',
            'ref_no' => 'REF-TDD-TEST-999',
            'contact_id' => 11,
            'transaction_date' => '2026-05-17 12:00:00',
            'final_total' => 100.00,
            'created_by' => 2,
        ]);

        // 2. Create a Purchase (status = pending)
        $purchase_id = DB::table('transactions')->insertGetId([
            'business_id' => 2,
            'type' => 'purchase',
            'status' => 'pending',
            'payment_status' => 'due',
            'invoice_no' => 'APN-TDD-999',
            'ref_no' => 'REF-TDD-TEST-999',
            'contact_id' => 11,
            'transaction_date' => '2026-05-17 12:00:00',
            'final_total' => 100.00,
            'created_by' => 2,
        ]);

        $updateData = [
            'is_purchase_order' => 0,
            'contact_id' => 11,
            'transaction_date' => '05/17/2026',
            'invoice_date' => '05/17/2026',
            'status' => 'pending',
            'ref_no' => 'REF-TDD-TEST-999',
            'location_id' => 2,
            'exchange_rate' => 1,
            'total_before_tax' => '100',
            'final_total' => '100',
            'purchases' => [
                [
                    'product_id' => 1,
                    'variation_id' => 1,
                    'quantity' => 10,
                    'pp_without_discount' => 10,
                    'purchase_price' => 10,
                    'purchase_price_inc_tax' => 10,
                    'item_tax' => 0,
                    'purchase_line_tax_id' => null,
                ]
            ],
            'payment' => [],
        ];

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => 2,
                'user.id' => 2,
                'business' => $business,
            ])
            ->put('/purchases/' . $purchase_id, $updateData);

        $response->assertStatus(302);
        $response->assertSessionHas('status');
        $status = session('status');
        $this->assertEquals(1, $status['success']);

        // Clean up
        DB::table('transactions')->whereIn('id', [$po_id, $purchase_id])->delete();
    }
}


