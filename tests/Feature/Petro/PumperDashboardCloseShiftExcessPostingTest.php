<?php

namespace Tests\Feature\Petro;

use App\User;
use App\Account;
use App\AccountTransaction;
use App\Utils\TransactionUtil;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementExcessPayment;

class PumperDashboardCloseShiftExcessPostingTest extends PetroTestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected int $accountsPayableId;
    protected int $accountsReceivableId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::where('business_id', $this->businessId)->firstOrFail();
        request()->setLaravelSession($this->app['session.store']);
        $business = \App\Business::find($this->businessId);
        $this->app['session']->put('business', $business->toArray());
        $this->app['session']->put('business.id', $this->businessId);
        $this->app['session']->put('user.business_id', $this->businessId);
        $this->app['session']->put('user.id', $this->userId);
        $this->actingAs($this->user);
        $this->accountsPayableId = $this->ensureLedgerAccount('Accounts Payable');
        $this->accountsReceivableId = $this->ensureLedgerAccount('Accounts Receivable');

        $subs = DB::connection('system')->table('subscriptions')
            ->where('business_id', $this->businessId)
            ->get();
        foreach ($subs as $sub) {
            $details = json_decode($sub->package_details, true) ?: [];
            $details['whatsapp_phone_no'] = '1234567890';
            DB::connection('system')->table('subscriptions')
                ->where('id', $sub->id)
                ->update(['package_details' => json_encode($details)]);
        }
    }

    /** @test */
    public function settlement_finalization_with_excess_posts_to_accounts_payable(): void
    {
        $settlement = $this->seedSettlement('ST-EXCESS-');

        // Seed excess payment
        DB::table('settlement_excess_payments')->insert([
            'business_id' => $this->businessId,
            'settlement_no' => $settlement->id,
            'amount' => 500,
            'current_excess' => 0,
            'note' => 'Excess test note',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $res = $this->actingAs($this->user)
            ->post('/petro/settlement', [
                'settlement_no' => $settlement->settlement_no,
                'no_change' => 0,
            ]);
        if ($res->status() !== 200 || !empty(json_decode($res->getContent(), true)['msg'])) {
            dump($res->getContent());
        }

        // Assert that an AccountTransaction is created for Accounts Payable (credit)
        $tx = AccountTransaction::where('account_id', $this->accountsPayableId)
            ->where('amount', 500)
            ->where('type', 'credit')
            ->first();

        $this->assertNotNull($tx, 'Expected AccountTransaction for Accounts Payable was not created.');

        // Assert that NO AccountTransaction is created for Accounts Receivable
        $arTx = AccountTransaction::where('account_id', $this->accountsReceivableId)
            ->where('amount', 500)
            ->first();

        $this->assertNull($arTx, 'Should not post excess to Accounts Receivable.');
    }

    /** @test */
    public function settlement_pd_finalization_with_excess_posts_to_accounts_payable(): void
    {
        $settlement = $this->seedSettlement('PDST-EXCESS-');

        // Seed excess payment
        DB::table('settlement_excess_payments')->insert([
            'business_id' => $this->businessId,
            'settlement_no' => $settlement->id,
            'amount' => 600,
            'current_excess' => 0,
            'note' => 'Excess PD test note',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->post('/petro/settlement-pd', [
                'settlement_no' => $settlement->settlement_no,
                'no_change' => 0,
            ]);

        // Assert that an AccountTransaction is created for Accounts Payable (credit)
        $tx = AccountTransaction::where('account_id', $this->accountsPayableId)
            ->where('amount', 600)
            ->where('type', 'credit')
            ->first();

        $this->assertNotNull($tx, 'Expected PD AccountTransaction for Accounts Payable was not created.');

        // Assert that NO AccountTransaction is created for Accounts Receivable
        $arTx = AccountTransaction::where('account_id', $this->accountsReceivableId)
            ->where('amount', 600)
            ->first();

        $this->assertNull($arTx, 'Should not post PD excess to Accounts Receivable.');
    }

    /** @test */
    public function payout_of_excess_debits_accounts_payable(): void
    {
        $cashAccountId = $this->ensureLedgerAccount('Cash');

        $inputs = [
            'amount' => 350,
            'method' => 'cash',
            'account_id' => $cashAccountId,
            'note' => null,
            'card_number' => null,
            'card_holder_name' => null,
            'card_transaction_number' => null,
            'card_type' => null,
            'card_month' => null,
            'card_year' => null,
            'card_security' => null,
            'cheque_number' => null,
            'bank_account_number' => null,
            'payment_ref_no' => 'PUMP-EXCESS-PAYOUT-' . uniqid(),
            'paid_on' => now()->toDateString(),
        ];

        // We need operator to have excess_amount in db
        DB::table('pump_operators')->where('id', $this->pumpOperatorId)->update(['excess_amount' => 500]);

        // Call payAtOnceExcessShortage for excess
        app(TransactionUtil::class)->payAtOnceExcessShortage(
            $inputs,
            'excess',
            $this->pumpOperatorId
        );

        // Assert that AccountTransaction is created for Accounts Payable (debit)
        $tx = AccountTransaction::where('account_id', $this->accountsPayableId)
            ->where('amount', 350)
            ->where('type', 'debit')
            ->first();

        $this->assertNotNull($tx, 'Expected AccountTransaction debiting Accounts Payable was not created.');

        // Assert that NO AccountTransaction is created for Accounts Receivable
        $arTx = AccountTransaction::where('account_id', $this->accountsReceivableId)
            ->where('amount', 350)
            ->first();

        $this->assertNull($arTx, 'Should not debit Accounts Receivable for excess payout.');
    }

    private function seedSettlement(string $prefix): Settlement
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        $id = DB::table('settlements')->insertGetId([
            'settlement_no' => $prefix . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift' => json_encode([]),
            'status' => 1,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Settlement::findOrFail($id);
    }

    private function ensureLedgerAccount(string $name): int
    {
        $accountId = DB::table('accounts')
            ->where('business_id', $this->businessId)
            ->where('name', $name)
            ->value('id');

        if ($accountId) {
            return (int) $accountId;
        }

        return DB::table('accounts')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => 'all',
            'name' => $name,
            'account_number' => 'TST-' . uniqid(),
            'account_type_id' => null,
            'created_by' => $this->userId,
            'is_main_account' => 0,
            'is_closed' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
