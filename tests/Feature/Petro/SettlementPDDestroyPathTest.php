<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Session\Store;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Http\Controllers\SettlementPDController;
use Modules\Petro\Services\SettlementPaymentReconciler;

/**
 * @group characterization
 */
class SettlementPDDestroyPathTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function destroy_path_wipes_linked_and_orphan_payment_rows_for_the_target_settlement_only(): void
    {
        $settlement = $this->seedSettlement();
        $otherSettlement = $this->seedSettlement();
        $reconciler = app(SettlementPaymentReconciler::class);

        foreach ([
            'settlement_card_payments' => ['amount' => 10, 'customer_id' => $this->contactId, 'card_type' => 1],
            'settlement_cash_payments' => ['amount' => 20, 'customer_id' => $this->contactId],
            'settlement_cheque_payments' => ['amount' => 30, 'customer_id' => $this->contactId],
            'settlement_credit_sale_payments' => $this->buildCreditSalePaymentData(['amount' => 40, 'sub_total' => 40]),
        ] as $table => $baseRow) {
            $reconciler->upsertOne($this->businessId, (string) $settlement->id, $table, array_merge($baseRow, [
                'pump_payment_id' => $this->seedPumpOperatorPayment(['payment_type' => $this->paymentTypeForTable($table)]),
            ]));
            $reconciler->upsertOne($this->businessId, (string) $settlement->id, $table, array_merge($baseRow, [
                'pump_payment_id' => null,
            ]));
            $reconciler->upsertOne($this->businessId, (string) $otherSettlement->id, $table, array_merge($baseRow, [
                'pump_payment_id' => null,
            ]));
        }

        $this->seedRequestSession();
        app(SettlementPDController::class)->deletePreviouseTransactions($settlement->id, true);

        foreach ([
            'settlement_card_payments',
            'settlement_cash_payments',
            'settlement_cheque_payments',
            'settlement_credit_sale_payments',
        ] as $table) {
            $this->assertSame(0, DB::table($table)->where('settlement_no', (string) $settlement->id)->count(), "{$table} target rows must be wiped.");
            $this->assertSame(1, DB::table($table)->where('settlement_no', (string) $otherSettlement->id)->count(), "{$table} sibling rows must survive.");
        }
    }

    private function seedSettlement(): Settlement
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => 'PDST-DST-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([]),
            'note' => null,
            'total_amount' => '100',
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
        $session->put('business.accounting_method', 'fifo');
        $session->put('user.id', $this->userId);
        request()->setLaravelSession($session);
    }

    private function paymentTypeForTable(string $table): string
    {
        return [
            'settlement_card_payments' => 'card',
            'settlement_cash_payments' => 'cash',
            'settlement_cheque_payments' => 'cheque',
            'settlement_credit_sale_payments' => 'credit',
        ][$table];
    }
}
