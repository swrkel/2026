<?php

namespace Tests\Feature\Petro;

use App\AccountTransaction;
use App\Account;
use App\Transaction;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class DirectSettlementPastDateOperationDateTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function settlement_transaction_datatable_shows_created_at_as_date_and_transaction_date_as_transaction_date(): void
    {
        $user = User::where('business_id', $this->businessId)->firstOrFail();

        $businessRow = DB::table('business')->where('id', $this->businessId)->first();
        $businessSession = (array) $businessRow;

        $session = app('session')->driver('array');
        $session->put('business.id', $this->businessId);
        $session->put('user.business_id', $this->businessId);
        $session->put('user.id', $user->id);
        $session->put('business', $businessSession);
        request()->setLaravelSession($session);

        $this->actingAs($user)->withSession([
            'business.id'      => $this->businessId,
            'user.business_id' => $this->businessId,
            'user.id'          => $user->id,
            'business'         => $businessSession,
            'currency'         => $this->currencySessionData(),
        ]);

        $account = Account::firstOrCreate(
            ['business_id' => $this->businessId, 'name' => 'Test Settlement Account'],
            ['account_number' => 'TSA-' . uniqid(), 'created_by' => $user->id, 'is_closed' => 0]
        );

        $pastDate = now()->subDays(10)->toDateString();
        $today = now()->toDateString();

        $locationId = DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        // Create settlement transaction with past date
        $transaction = Transaction::create([
            'business_id'      => $this->businessId,
            'location_id'      => $locationId,
            'type'             => 'sell',
            'sub_type'         => 'settlement',
            'status'           => 'final',
            'is_settlement'    => 1,
            'contact_id'       => $this->contactId,
            'transaction_date' => $pastDate . ' 08:00:00',
            'invoice_no'       => 'TEST-SETTLE-' . uniqid(),
            'final_total'      => 1000.00,
            'created_by'       => $user->id,
            'payment_status'   => 'paid',
        ]);

        // Account transaction row for settlement
        $accountTxn = AccountTransaction::create([
            'account_id' => $account->id,
            'transaction_id' => $transaction->id,
            'type' => 'credit',
            'amount' => 1000.00,
            'operation_date' => $transaction->transaction_date,
            'created_by' => $user->id,
            'business_id' => $this->businessId,
        ]);

        // Create regular non-settlement transaction with past date
        $regularTxn = Transaction::create([
            'business_id'      => $this->businessId,
            'location_id'      => $locationId,
            'type'             => 'sell',
            'sub_type'         => null,
            'status'           => 'final',
            'is_settlement'    => 0,
            'contact_id'       => $this->contactId,
            'transaction_date' => $pastDate . ' 12:00:00',
            'invoice_no'       => 'TEST-REGULAR-' . uniqid(),
            'final_total'      => 500.00,
            'created_by'       => $user->id,
            'payment_status'   => 'paid',
        ]);

        $regularAccountTxn = AccountTransaction::create([
            'account_id' => $account->id,
            'transaction_id' => $regularTxn->id,
            'type' => 'credit',
            'amount' => 500.00,
            'operation_date' => $regularTxn->transaction_date,
            'created_by' => $user->id,
            'business_id' => $this->businessId,
        ]);

        // Request Datatable JSON
        $response = $this->getJson(action('AccountController@show', [$account->id]), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertNotEmpty($data);

        // Find the settlement row
        $settlementRow = collect($data)->first(function ($row) use ($transaction) {
            return ($row['transaction_id'] ?? null) == $transaction->id;
        });

        // Find the regular row
        $regularRow = collect($data)->first(function ($row) use ($regularTxn) {
            return ($row['transaction_id'] ?? null) == $regularTxn->id;
        });

        $this->assertNotNull($settlementRow, 'Settlement row should be present');
        $this->assertNotNull($regularRow, 'Regular row should be present');

        $util = new \App\Utils\Util();
        $expectedCreatedAtFormatted = $util->format_date($accountTxn->created_at, false);
        $expectedTransactionDateFormatted = $util->format_date($transaction->transaction_date, false);
        $expectedRegularDateFormatted = $util->format_date($regularAccountTxn->operation_date, false);

        // Settlement row Date (operation_date column in JSON) should display finalized date (created_at)
        $this->assertEquals($expectedCreatedAtFormatted, $settlementRow['operation_date']);
        // Settlement row Transaction Date (realize_date column in JSON) should display past transaction date
        $this->assertEquals($expectedTransactionDateFormatted, $settlementRow['realize_date']);

        // Regular row Date should display operation_date
        $this->assertEquals($expectedRegularDateFormatted, $regularRow['operation_date']);
        // Regular row Transaction Date should display transaction_date
        $this->assertEquals($expectedTransactionDateFormatted, $regularRow['realize_date']);
    }
}
