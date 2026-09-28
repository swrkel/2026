<?php

namespace Tests\Feature;

use App\User;
use App\Business;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class PurchaseOrderListFilterTest extends TestCase
{
    public function test_purchase_order_list_filter_contains_only_apo_numbers()
    {
        $user = User::find(2);

        // Delete any leftover test data
        DB::table('transactions')->whereIn('invoice_no', ['APO000999', 'APN000888'])->delete();

        // Insert a test Purchase Order (APO prefix, status ordered)
        DB::table('transactions')->insert([
            'business_id' => 2,
            'type' => 'purchase',
            'status' => 'ordered',
            'payment_status' => 'due',
            'invoice_no' => 'APO000999',
            'order_no' => 'APO000999',
            'ref_no' => 'REF-999',
            'contact_id' => 11,
            'transaction_date' => '2026-05-17 12:00:00',
            'final_total' => 100.00,
            'created_by' => 2,
        ]);

        // Insert a normal Purchase (APN prefix, status received)
        DB::table('transactions')->insert([
            'business_id' => 2,
            'type' => 'purchase',
            'status' => 'received',
            'payment_status' => 'paid',
            'invoice_no' => 'APN000888',
            'order_no' => 'APN000888',
            'ref_no' => 'REF-888',
            'contact_id' => 11,
            'transaction_date' => '2026-05-17 12:00:00',
            'final_total' => 150.00,
            'created_by' => 2,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => 2,
                'user.id' => 2,
                'business' => Business::find(2),
            ])
            ->get('/purchases/orders');

        $response->assertStatus(200);

        $ordernos = $response->original->getData()['ordernos'];

        // Assert that our newly created APO number is in the dropdown
        $this->assertContains('APO000999', $ordernos, "The APO000999 purchase order must be in the dropdown.");

        // Assert that the APN number is NOT in the dropdown
        $this->assertNotContains('APN000888', $ordernos, "The APN000888 purchase number must NOT be in the dropdown.");

        // Clean up
        DB::table('transactions')->whereIn('invoice_no', ['APO000999', 'APN000888'])->delete();
    }

    public function test_purchase_list_filter_contains_only_apo_numbers()
    {
        $user = User::find(2);

        // Delete any leftover test data
        DB::table('transactions')->whereIn('invoice_no', ['APO000777', 'APN000666'])->delete();

        // Insert a test Purchase Order (APO prefix, status ordered)
        DB::table('transactions')->insert([
            'business_id' => 2,
            'type' => 'purchase',
            'status' => 'ordered',
            'payment_status' => 'due',
            'invoice_no' => 'APO000777',
            'order_no' => 'APO000777',
            'ref_no' => 'REF-777',
            'contact_id' => 11,
            'transaction_date' => '2026-05-17 12:00:00',
            'final_total' => 100.00,
            'created_by' => 2,
        ]);

        // Insert a normal Purchase (APN prefix, status received)
        DB::table('transactions')->insert([
            'business_id' => 2,
            'type' => 'purchase',
            'status' => 'received',
            'payment_status' => 'paid',
            'invoice_no' => 'APN000666',
            'order_no' => 'APN000666',
            'ref_no' => 'REF-666',
            'contact_id' => 11,
            'transaction_date' => '2026-05-17 12:00:00',
            'final_total' => 150.00,
            'created_by' => 2,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => 2,
                'user.id' => 2,
                'business' => Business::find(2),
            ])
            ->get('/purchases');

        $response->assertStatus(200);

        $ordernos = $response->original->getData()['ordernos'];

        // Assert that the APN number is NOT in the dropdown for normal purchases list page too
        $this->assertNotContains('APN000666', $ordernos, "The APN000666 purchase number must NOT be in the dropdown.");

        // Clean up
        DB::table('transactions')->whereIn('invoice_no', ['APO000777', 'APN000666'])->delete();
    }
}
