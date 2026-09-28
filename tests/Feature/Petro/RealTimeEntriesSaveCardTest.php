<?php

namespace Tests\Feature\Petro;

use App\Account;
use App\AccountGroup;
use App\AccountTransaction;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class RealTimeEntriesSaveCardTest extends PetroTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (\Illuminate\Support\Facades\Schema::connection('system')->hasTable('subscriptions')) {
            DB::connection('system')->table('subscriptions')
                ->where('business_id', $this->businessId)
                ->update([
                    'package_details' => json_encode([
                        'petro_pd_module' => 1,
                        'real_time_entries' => 1,
                    ])
                ]);
        }
    }

    /** @test */
    public function save_card_posts_debit_to_selected_card_account(): void
    {
        $user = User::where('business_id', $this->businessId)->firstOrFail();

        // Create Card Account Group
        $cardGroup = AccountGroup::firstOrCreate([
            'business_id' => $this->businessId,
            'name' => 'Card',
        ]);

        // Create specific Card Account
        $visaAccount = Account::create([
            'business_id' => $this->businessId,
            'name' => 'VISA Test Account',
            'account_number' => '12345',
            'asset_type' => $cardGroup->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->postJson('/real-time-entries/save-card', [
                'amount' => 500,
                'card_type' => $visaAccount->id,
                'slip_no' => 'SLIP-12345',
                'shift_number' => '10',
                'transaction_date' => now()->toDateString(),
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        // Assert that an AccountTransaction was debited to VISA Test Account
        $transaction = AccountTransaction::where('account_id', $visaAccount->id)
            ->where('amount', 500)
            ->where('type', 'debit')
            ->first();

        $this->assertNotNull($transaction, 'Account transaction was not posted to the selected card account.');
    }
}
