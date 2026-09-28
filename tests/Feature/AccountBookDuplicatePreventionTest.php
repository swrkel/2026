<?php

namespace Tests\Feature;

use App\Business;
use App\Account;
use App\AccountTransaction;
use App\Transaction;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountBookDuplicatePreventionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_account_book_filters_duplicate_settlement_entries_when_realtime_exists()
    {
        // 1. Setup business/user
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business in DB');
        }
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        // 2. Setup Cash Account
        $cash_account = Account::firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Cash'],
            [
                'account_number' => 'CASH-1234',
                'account_type_id' => 1,
                'created_by' => $user->id,
            ]
        );

        // 3. Seed Real Time Cash AccountTransaction (note has Real Time and Settlement No. ST-TEST-999)
        $rt_at = AccountTransaction::create([
            'business_id' => $business->id,
            'account_id' => $cash_account->id,
            'type' => 'debit',
            'amount' => 8000.00,
            'operation_date' => '2026-05-26 10:00:00',
            'note' => 'Real Time Cash - Shift No. 1 - Settlement No. ST-TEST-999',
            'created_by' => $user->id,
        ]);

        // 4. Seed finalized settlement Transaction with invoice_no = ST-TEST-999
        $settlement_txn = Transaction::create([
            'business_id' => $business->id,
            'type' => 'sell',
            'sub_type' => 'settlement',
            'status' => 'final',
            'payment_status' => 'paid',
            'invoice_no' => 'ST-TEST-999',
            'transaction_date' => '2026-05-26 12:00:00',
            'final_total' => 8000.00,
            'is_settlement' => 1,
            'created_by' => $user->id,
        ]);

        // 5. Seed settlement cash AccountTransaction linked to the settlement Transaction
        $settlement_at = AccountTransaction::create([
            'business_id' => $business->id,
            'account_id' => $cash_account->id,
            'type' => 'debit',
            'amount' => 8000.00,
            'operation_date' => '2026-05-26 12:00:00',
            'note' => 'Settlement No: ST-TEST-999',
            'transaction_id' => $settlement_txn->id,
            'created_by' => $user->id,
        ]);

        // 6. Call AccountController@show via AJAX
        $response = $this->withSession([
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ])->getJson('/accounting-module/account/' . $cash_account->id . '?start_date=2026-05-20&end_date=2026-05-30&date_based_on=transaction_date', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $rt_count = 0;
        $settlement_only_count = 0;
        foreach ($data as $row) {
            $desc = strip_tags($row['description']);
            $note = $row['note'] ?? '';
            $is_rt = (strpos($desc, 'Real Time Cash') !== false || strpos($note, 'Real Time Cash') !== false);
            if ($is_rt) {
                $rt_count++;
            } else {
                $is_settlement = (strpos($desc, 'ST-TEST-999') !== false || strpos($note, 'ST-TEST-999') !== false);
                if ($is_settlement) {
                    $settlement_only_count++;
                }
            }
        }

        fwrite(STDERR, "\nTotal entries returned: " . count($data) . "\n");
        fwrite(STDERR, "Real-time entries found: " . $rt_count . "\n");
        fwrite(STDERR, "Settlement-only entries found: " . $settlement_only_count . "\n");

        $this->assertCount(1, $data, "Should return exactly one entry.");
        $this->assertEquals(1, $rt_count, "The entry should be the real-time entry.");
        $this->assertEquals(0, $settlement_only_count, "Settlement-only entries should be filtered out.");
    }
}
