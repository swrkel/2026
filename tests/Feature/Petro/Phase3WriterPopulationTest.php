<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Session\Store;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Http\Controllers\SettlementPDController;

/**
 * @group characterization
 */
class Phase3WriterPopulationTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function pd_create_transaction_populates_petro_settlement_id(): void
    {
        $settlement = $this->seedPdSettlement();
        $this->seedRequestSession();

        $transaction = app(SettlementPDController::class)->createTransaction(
            $settlement,
            250.00,
            $this->contactId,
            $this->pumpOperatorId,
            'settlement',
            'cash_payment',
            $settlement->settlement_no
        );

        $this->assertSame(
            $settlement->id,
            (int) DB::table('transactions')->where('id', $transaction->id)->value('petro_settlement_id')
        );
    }

    /** @test */
    public function pd_create_credit_sell_transactions_populates_petro_settlement_id(): void
    {
        $settlement = $this->seedPdSettlement();
        $sale = (object) $this->buildCreditSalePaymentData([
            'id' => 987654,
            'business_id' => $this->businessId,
            'amount' => 325.00,
            'total_discount' => 25.00,
            'customer_reference' => 'PHASE3-' . uniqid(),
        ]);
        $this->seedRequestSession();

        $transaction = app(SettlementPDController::class)->createCreditSellTransactions(
            $settlement,
            $sale,
            $settlement->location_id
        );

        $this->assertSame(
            $settlement->id,
            (int) DB::table('transactions')->where('id', $transaction->id)->value('petro_settlement_id')
        );
    }

    private function seedPdSettlement(): Settlement
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => 'PDST-PHASE3-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([10]),
            'note' => null,
            'total_amount' => '0',
            'status' => 0,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Settlement::findOrFail($settlementId);
    }

    private function seedRequestSession(): void
    {
        /** @var Store $session */
        $session = app('session.store');
        $session->put('business.id', $this->businessId);
        $session->put('user.id', $this->userId);
        request()->setLaravelSession($session);
    }
}
