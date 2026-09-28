<?php

namespace Tests\Feature;

use App\User;
use App\Business;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class PurchaseListNewColumnsTest extends TestCase
{
    public function test_purchase_datatable_json_contains_created_at_and_invoice_date()
    {
        $user = User::find(2);

        // Delete any leftover test data
        DB::table('transactions')->where('invoice_no', 'TEST-DATE-999')->delete();

        // Insert a test Purchase with specific created_at and invoice_date
        $transactionId = DB::table('transactions')->insertGetId([
            'business_id' => 2,
            'type' => 'purchase',
            'status' => 'received',
            'payment_status' => 'due',
            'invoice_no' => 'TEST-DATE-999',
            'ref_no' => 'REF-999',
            'contact_id' => 11,
            'location_id' => 2,
            'transaction_date' => '2026-05-17 12:00:00',
            'created_at' => '2026-05-17 10:30:15',
            'invoice_date' => '2026-05-16 00:00:00',
            'final_total' => 100.00,
            'created_by' => 2,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => 2,
                'user.id' => 2,
                'business' => Business::find(2),
            ])
            ->get('/purchases', [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertStatus(200);
        $data = $response->json()['data'];

        // Find our inserted transaction in the response data
        $found = null;
        foreach ($data as $row) {
            if (strpos($row['invoice_no'], 'TEST-DATE-999') !== false) {
                $found = $row;
                break;
            }
        }

        $this->assertNotNull($found, "The test transaction should be present in the datatable JSON response.");
        
        // Clean up before assertion just in case it fails
        DB::table('transactions')->where('id', $transactionId)->delete();

        $this->assertArrayHasKey('created_at', $found, "The row must contain 'created_at' column.");
        $this->assertArrayHasKey('invoice_date', $found, "The row must contain 'invoice_date' column.");
        $this->assertEquals('2026-05-17 10:30', $found['created_at']);
        $this->assertEquals('2026-05-16', $found['invoice_date']);
    }
}
