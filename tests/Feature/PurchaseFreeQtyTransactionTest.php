<?php

namespace Tests\Feature;

use App\User;
use App\Business;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Transaction;

class PurchaseFreeQtyTransactionTest extends TestCase
{
    public function test_purchase_saved_without_free_qty_as_new_transaction()
    {
        $this->withoutExceptionHandling();
        $user = User::find(2);

        // Delete any leftover test data
        DB::table('transactions')->whereIn('invoice_no', ['APN-TEST-FREE-1', 'FREE-APN-TEST-FREE-1'])->delete();

        $data = [
            'is_purchase_order' => 0,
            'contact_id' => 11, // CPC Supplier
            'transaction_date' => '05/17/2026',
            'invoice_date' => '05/17/2026',
            'store_id' => 1,
            'status' => 'received',
            'ref_no' => 'REF-FREE-1',
            'invoice_no' => 'APN-TEST-FREE-1',
            'location_id' => 2, // BL0002
            'exchange_rate' => 1,
            'total_before_tax' => '100',
            'final_total' => '100',
            'free_qty_as_new_transaction' => 0,
            'purchases' => [
                [
                    'product_id' => 1,
                    'variation_id' => 1,
                    'quantity' => 10,
                    'free_qty' => 5,
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

        // Assert main transaction exists
        $main_txn = Transaction::where('invoice_no', 'APN-TEST-FREE-1')->first();
        $this->assertNotNull($main_txn);

        // Assert main purchase line quantity includes free_qty (bonus_qty = 5, quantity = 10 + 5 = 15)
        $purchase_line = $main_txn->purchase_lines->first();
        $this->assertEquals(15, (float)$purchase_line->quantity);
        $this->assertEquals(5, (float)$purchase_line->bonus_qty);

        // Assert no separate FREE transaction was created
        $free_txn_exists = Transaction::where('invoice_no', 'FREE-APN-TEST-FREE-1')->exists();
        $this->assertFalse($free_txn_exists);

        // Clean up
        DB::table('transactions')->whereIn('invoice_no', ['APN-TEST-FREE-1', 'FREE-APN-TEST-FREE-1'])->delete();
    }

    public function test_purchase_saved_with_free_qty_as_new_transaction()
    {
        $this->withoutExceptionHandling();
        $user = User::find(2);

        // Delete any leftover test data
        DB::table('transactions')->whereIn('invoice_no', ['APN-TEST-FREE-2', 'FREE-APN-TEST-FREE-2'])->delete();

        $data = [
            'is_purchase_order' => 0,
            'contact_id' => 11, // CPC Supplier (name: 'CPC Supplier')
            'transaction_date' => '05/17/2026',
            'invoice_date' => '05/17/2026',
            'store_id' => 1,
            'status' => 'received',
            'ref_no' => 'REF-FREE-2',
            'invoice_no' => 'APN-TEST-FREE-2',
            'location_id' => 2, // BL0002
            'exchange_rate' => 1,
            'total_before_tax' => '100',
            'final_total' => '100',
            'free_qty_as_new_transaction' => 1,
            'purchases' => [
                [
                    'product_id' => 1,
                    'variation_id' => 1,
                    'quantity' => 10,
                    'free_qty' => 5,
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

        // Assert main transaction exists
        $main_txn = Transaction::where('invoice_no', 'APN-TEST-FREE-2')->first();
        $this->assertNotNull($main_txn);

        // Assert main purchase line quantity is ONLY paid quantity (10) and bonus_qty is 0
        $purchase_line = $main_txn->purchase_lines->first();
        $this->assertEquals(10, (float)$purchase_line->quantity);
        $this->assertEquals(0, (float)$purchase_line->bonus_qty);

        // Assert separate FREE transaction was created
        $free_txn = Transaction::where('invoice_no', 'FREE-APN-TEST-FREE-2')->first();
        $this->assertNotNull($free_txn);
        $this->assertEquals('purchase', $free_txn->type);
        $this->assertEquals('received', $free_txn->status);
        $this->assertEquals(0, (float)$free_txn->final_total);
        $this->assertEquals('FREE-REF-FREE-2', $free_txn->ref_no);

        // Assert the descriptive tag in additional_notes
        $this->assertStringContainsString('Free Qty given by Supplier', $free_txn->additional_notes);
        $this->assertStringContainsString('P. Invoice No APN-TEST-FREE-2', $free_txn->additional_notes);

        // Assert the free transaction's purchase line quantity is exactly the free_qty (5)
        $free_purchase_line = $free_txn->purchase_lines->first();
        $this->assertEquals(5, (float)$free_purchase_line->quantity);
        $this->assertEquals(0, (float)$free_purchase_line->bonus_qty);

        $debit_entry = \App\AccountTransaction::where('transaction_id', $main_txn->id)
            ->where('type', 'debit')
            ->where('sub_type', 'free_product')
            ->first();
        
        $this->assertNotNull($debit_entry);
        $this->assertEquals(50.0, (float)$debit_entry->amount);
        $this->assertStringContainsString('color: red', $debit_entry->note);
        $this->assertStringContainsString('Free Qty', $debit_entry->note);
        $this->assertStringContainsString('Supplier: CPC', $debit_entry->note);
        $this->assertStringContainsString('APN No: APN-TEST-FREE-2', $debit_entry->note);
        $this->assertStringContainsString('Reference No: REF-FREE-2', $debit_entry->note);

        // Income - Free Products or Samples Account should have a credit entry of 50
        $credit_entry = \App\AccountTransaction::where('transaction_id', $main_txn->id)
            ->where('type', 'credit')
            ->where('sub_type', 'free_product')
            ->first();
        $this->assertNotNull($credit_entry);
        $this->assertEquals(50.0, (float)$credit_entry->amount);
        $this->assertStringContainsString('color: red', $credit_entry->note);
        $this->assertStringContainsString('Free Qty', $credit_entry->note);
        $this->assertStringContainsString('Supplier: CPC', $credit_entry->note);
        $this->assertStringContainsString('APN No: APN-TEST-FREE-2', $credit_entry->note);
        $this->assertStringContainsString('Reference No: REF-FREE-2', $credit_entry->note);

        // Clean up
        DB::table('account_transactions')->where('transaction_id', $main_txn->id)->delete();
        DB::table('transactions')->whereIn('invoice_no', ['APN-TEST-FREE-2', 'FREE-APN-TEST-FREE-2'])->delete();
    }
}
