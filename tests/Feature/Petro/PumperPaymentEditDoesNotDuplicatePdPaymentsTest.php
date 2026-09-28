<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Http\Controllers\AddPaymentController;
use Modules\Petro\Http\Controllers\SettlementPDController;

class PumperPaymentEditDoesNotDuplicatePdPaymentsTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function editing_one_cash_payment_does_not_rewrite_sibling_daily_collections_in_the_same_form(): void
    {
        $collectionNo = 'CF-CASH-' . uniqid();
        $settlementId = $this->seedSettlement();

        $cashA = $this->seedPumpOperatorPayment([
            'payment_type' => 'cash',
            'payment_amount' => 1000,
            'collection_form_no' => $collectionNo,
        ]);
        $cashB = $this->seedPumpOperatorPayment([
            'payment_type' => 'cash',
            'payment_amount' => 2000,
            'collection_form_no' => $collectionNo,
        ]);

        $dailyA = $this->insertDailyCollection($collectionNo, 1000);
        $dailyB = $this->insertDailyCollection($collectionNo, 2000);

        DB::table('settlement_cash_payments')->insert([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementId,
            'customer_id' => $this->contactId,
            'amount' => 1000,
            'customer_payment_id' => $cashA,
            'pump_payment_id' => $cashA,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('settlement_cash_payments')->insert([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementId,
            'customer_id' => $this->contactId,
            'amount' => 2000,
            'customer_payment_id' => $cashB,
            'pump_payment_id' => $cashB,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->putJson('/petro/pump-operator-payments/' . $cashA, [
                'payment_amount' => 1500,
                'note' => 'cash edited',
                'payment_type' => 'cash',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('1500.0000', number_format((float) DB::table('daily_collections')->where('id', $dailyA)->value('current_amount'), 4, '.', ''));
        $this->assertSame('2000.0000', number_format((float) DB::table('daily_collections')->where('id', $dailyB)->value('current_amount'), 4, '.', ''));
        $this->assertSame(2, DB::table('settlement_cash_payments')->where('settlement_no', $settlementId)->count());
        $this->assertSame('1500.0000', number_format((float) DB::table('settlement_cash_payments')->where('pump_payment_id', $cashA)->value('amount'), 4, '.', ''));
        $this->assertSame('2000.0000', number_format((float) DB::table('settlement_cash_payments')->where('pump_payment_id', $cashB)->value('amount'), 4, '.', ''));
    }

    /** @test */
    public function editing_one_card_payment_does_not_rewrite_all_cards_in_the_same_form(): void
    {
        $collectionNo = 'CF-CARD-' . uniqid();
        $settlementId = $this->seedSettlement();

        $cardA = $this->seedPumpOperatorPayment([
            'payment_type' => 'card',
            'payment_amount' => 100,
            'collection_form_no' => $collectionNo,
        ]);
        $cardB = $this->seedPumpOperatorPayment([
            'payment_type' => 'card',
            'payment_amount' => 200,
            'collection_form_no' => $collectionNo,
        ]);

        $dailyA = $this->insertDailyCard($collectionNo, 100, 'SLIP-A');
        $dailyB = $this->insertDailyCard($collectionNo, 200, 'SLIP-B');

        DB::table('settlement_card_payments')->insert([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementId,
            'customer_id' => $this->contactId,
            'card_type' => 1,
            'amount' => 100,
            'customer_payment_id' => $cardA,
            'pump_payment_id' => $cardA,
            'daily_card_id' => $dailyA,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('settlement_card_payments')->insert([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementId,
            'customer_id' => $this->contactId,
            'card_type' => 1,
            'amount' => 200,
            'customer_payment_id' => $cardB,
            'pump_payment_id' => $cardB,
            'daily_card_id' => $dailyB,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->putJson('/petro/pump-operator-payments/' . $cardA, [
                'payment_amount' => 150,
                'note' => 'card edited',
                'payment_type' => 'card',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('150.0000', number_format((float) DB::table('daily_cards')->where('id', $dailyA)->value('amount'), 4, '.', ''));
        $this->assertSame('200.0000', number_format((float) DB::table('daily_cards')->where('id', $dailyB)->value('amount'), 4, '.', ''));
        $this->assertSame(2, DB::table('settlement_card_payments')->where('settlement_no', $settlementId)->count());
        $this->assertSame('150.0000', number_format((float) DB::table('settlement_card_payments')->where('pump_payment_id', $cardA)->value('amount'), 4, '.', ''));
        $this->assertSame('200.0000', number_format((float) DB::table('settlement_card_payments')->where('pump_payment_id', $cardB)->value('amount'), 4, '.', ''));
    }

    /** @test */
    public function pd_edit_reload_collapses_legacy_duplicate_card_rows_by_pump_payment_identity(): void
    {
        $collectionNo = 'CF-CARD-DUP-' . uniqid();
        $settlementId = $this->seedSettlement();
        $settlementNo = DB::table('settlements')->where('id', $settlementId)->value('settlement_no');

        $cardA = $this->seedPumpOperatorPayment([
            'payment_type' => 'card',
            'payment_amount' => 1001,
            'collection_form_no' => $collectionNo,
        ]);
        $cardB = $this->seedPumpOperatorPayment([
            'payment_type' => 'card',
            'payment_amount' => 2000,
            'collection_form_no' => $collectionNo,
        ]);

        $dailyA = $this->insertDailyCard($collectionNo, 1001, '01');
        $dailyB = $this->insertDailyCard($collectionNo, 2000, '02');

        foreach ([
            [$settlementId, $cardA, $dailyA, 1001],
            [$settlementId, $cardB, $dailyB, 2000],
            [$settlementNo, $cardA, $dailyA, 1001],
            [$settlementNo, $cardB, $dailyB, 2000],
        ] as [$rowSettlementNo, $pumpPaymentId, $dailyCardId, $amount]) {
            DB::table('settlement_card_payments')->insert([
                'business_id' => $this->businessId,
                'settlement_no' => $rowSettlementNo,
                'customer_id' => $this->contactId,
                'card_type' => 1,
                'amount' => $amount,
                'customer_payment_id' => $pumpPaymentId,
                'pump_payment_id' => $pumpPaymentId,
                'daily_card_id' => $dailyCardId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $settlement = Settlement::with('card_payments')->findOrFail($settlementId);
        $method = new \ReflectionMethod(SettlementPDController::class, 'reloadSettlementPayments');
        $method->setAccessible(true);
        $method->invoke(app(SettlementPDController::class), $settlement);

        $this->assertCount(2, $settlement->card_payments);
        $this->assertSame([$cardA, $cardB], $settlement->card_payments->pluck('pump_payment_id')->map(fn ($id) => (int) $id)->values()->all());
        $this->assertSame('3001.0000', number_format((float) $settlement->card_payments->sum('amount'), 4, '.', ''));
    }

    /** @test */
    public function pd_payment_modal_shows_one_legacy_card_row_per_pump_payment_identity(): void
    {
        $source = file_get_contents((new \ReflectionClass(AddPaymentController::class))->getFileName());
        $pdBranch = strpos($source, 'if ($request->type === \'settlement_pd\')');
        $pumpIdentity = strpos($source, 'if (!empty($item->pump_payment_id))', $pdBranch);
        $dailyIdentity = strpos($source, 'if (!empty($item->daily_card_id))', $pdBranch);
        $rowIdentity = strpos($source, 'if (!empty($item->id))', $pdBranch);

        $this->assertNotFalse($pdBranch);
        $this->assertNotFalse($pumpIdentity);
        $this->assertNotFalse($dailyIdentity);
        $this->assertNotFalse($rowIdentity);
        $this->assertLessThan($rowIdentity, $pumpIdentity);
        $this->assertLessThan($rowIdentity, $dailyIdentity);
    }

    /** @test */
    public function direct_settlement_preview_shows_manual_card_payment_without_daily_card_shift_link(): void
    {
        $settlementId = $this->seedSettlement(['work_shift' => json_encode([987654])]);
        DB::table('settlement_card_payments')->insert([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementId,
            'customer_id' => $this->contactId,
            'card_type' => 1,
            'card_number' => 'CARD-123',
            'amount' => 4321,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
                'currency' => $this->currencySessionData(),
            ])
            ->get('/petro/settlement/payment/preview/' . $settlementId);

        $response->assertOk();
        $response->assertSee('CARD-123', false);
        $response->assertSee('4,321.00', false);
    }

    /** @test */
    public function direct_settlement_preview_shows_card_payment_saved_against_settlement_number(): void
    {
        $settlementNo = 'PDST-PREVIEW-' . uniqid();
        $settlementId = $this->seedSettlement([
            'settlement_no' => $settlementNo,
            'work_shift' => json_encode([987654]),
        ]);

        DB::table('settlement_card_payments')->insert([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementNo,
            'customer_id' => $this->contactId,
            'card_type' => 1,
            'card_number' => 'CARD-BY-NO',
            'amount' => 7654,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
                'currency' => $this->currencySessionData(),
            ])
            ->get('/petro/settlement/payment/preview/' . $settlementId);

        $response->assertOk();
        $response->assertSee('CARD-BY-NO', false);
        $response->assertSee('7,654.00', false);
    }

    private function seedSettlement(array $overrides = []): int
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        return DB::table('settlements')->insertGetId(array_merge([
            'settlement_no' => 'PDST-EDIT-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift' => json_encode([]),
            'status' => 0,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function insertDailyCollection(string $collectionNo, float $amount): int
    {
        return DB::table('daily_collections')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_form_no' => $collectionNo,
            'type' => 'daily_collection',
            'current_amount' => $amount,
            'balance_collection' => 0,
            'created_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertDailyCard(string $collectionNo, float $amount, string $slipNo): int
    {
        $data = [
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_no' => $collectionNo,
            'amount' => $amount,
            'slip_no' => $slipNo,
            'card_type' => 1,
            'customer_id' => $this->contactId,
            'date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('daily_cards', 'type')) {
            $data['type'] = 'daily_collection';
        }
        if (Schema::hasColumn('daily_cards', 'card_number')) {
            $data['card_number'] = 'CARD-' . uniqid();
        }

        return DB::table('daily_cards')->insertGetId($data);
    }
}
