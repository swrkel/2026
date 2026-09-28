<?php

namespace Tests\Feature;

use App\Business;
use App\Account;
use App\AccountTransaction;
use App\Transaction;
use App\User;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\DailyCollection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PDSettlementCashAccountPreventionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_cash_account_book_prevents_duplicate_daily_collection_entries_for_pd_settlement()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business in DB');
        }
        $user = User::where('business_id', $business->id)->first();
        $this->actingAs($user);

        // 1. Setup Cash Account
        $cash_account = Account::firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Cash'],
            [
                'account_number' => 'CASH-9999',
                'account_type_id' => 1,
                'created_by' => $user->id,
            ]
        );

        // 2. Create Petro PD Settlement Model
        $settlement = Settlement::create([
            'business_id' => $business->id,
            'settlement_no' => 'ST-PD-999',
            'pump_operator_id' => 1,
            'business_location_id' => 1,
            'status' => 'final',
        ]);

        // 3. Create a transaction of type sell and sub_type settlement
        $settlement_txn = Transaction::create([
            'business_id' => $business->id,
            'type' => 'sell',
            'sub_type' => 'settlement',
            'status' => 'final',
            'payment_status' => 'paid',
            'invoice_no' => 'ST-PD-999',
            'transaction_date' => '2026-05-30 10:00:00',
            'final_total' => 10000.00,
            'created_by' => $user->id,
        ]);

        // 4. Create multiple AccountTransaction entries for the same settlement transaction in Cash Account
        // (to simulate the clashing parent rows that cause duplicates)
        $at1 = AccountTransaction::create([
            'business_id' => $business->id,
            'account_id' => $cash_account->id,
            'type' => 'debit',
            'amount' => 5000.00,
            'operation_date' => '2026-05-30 10:00:00',
            'created_at' => '2026-05-30 10:00:00',
            'transaction_id' => $settlement_txn->id,
            'created_by' => $user->id,
        ]);

        $at2 = AccountTransaction::create([
            'business_id' => $business->id,
            'account_id' => $cash_account->id,
            'type' => 'debit',
            'amount' => 5000.00,
            'operation_date' => '2026-05-30 10:00:00',
            'created_at' => '2026-05-30 10:00:00',
            'transaction_id' => $settlement_txn->id,
            'created_by' => $user->id,
        ]);

        // 5. Create multiple DailyCollection records associated with the settlement
        // (Simulate actual dashboard entries: e.g. two entries of 5000.00 each)
        $dc1 = DailyCollection::create([
            'business_id' => $business->id,
            'settlement_id' => $settlement->id,
            'pump_operator_id' => $settlement->pump_operator_id,
            'balance_collection' => 5000.00,
            'settlement_date' => '2026-05-30',
            'collection_form_no' => 'COL-111',
            'created_by' => $user->id,
        ]);

        $dc2 = DailyCollection::create([
            'business_id' => $business->id,
            'settlement_id' => $settlement->id,
            'pump_operator_id' => $settlement->pump_operator_id,
            'balance_collection' => 5000.00,
            'settlement_date' => '2026-05-30',
            'collection_form_no' => 'COL-222',
            'created_by' => $user->id,
        ]);

        // 6. Query AccountController show via AJAX
        $response = $this->withSession([
            'user.business_id' => $business->id,
            'user.id' => $user->id,
        ])->getJson('/accounting-module/account/' . $cash_account->id . '?start_date=2026-05-20&end_date=2026-05-30&date_based_on=transaction_date', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        // Filter returned rows related to our settlement transaction id
        $matchedRows = array_filter($data, function ($row) use ($settlement_txn) {
            return isset($row['transaction_id']) && $row['transaction_id'] == $settlement_txn->id;
        });

        // We expect exactly 2 entries (one for dc1 and one for dc2)
        // Without the fix, it would multiply them: 2 AccountTransactions * 2 DailyCollections = 4 entries!
        $this->assertCount(2, $matchedRows, "Should return exactly two distinct DailyCollection entries for the settlement.");

        $slips = array_map(function ($row) {
            return $row['slip_no'];
        }, $matchedRows);

        $this->assertContains('COL-111', $slips);
        $this->assertContains('COL-222', $slips);
    }
}
