<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\Settlement;

/**
 * Regression test: settlement-pd/print/{id} must only display credit sales
 * belonging to the shift(s) stored in settlement.work_shift.
 *
 * Scenario: operator has credit sales in Shift 1 (CF1, 15,000) AND Shift 4
 * (CF4, 25,000). The settlement is keyed to Shift 4 only. The print page
 * must show 25,000.00 and NOT show 15,000.00.
 *
 * The store() POST path shares the same bug (unfiltered credit sales in the
 * rendered print HTML) but cannot be exercised here via HTTP because the
 * full business-session bootstrapping is too expensive in test context.
 * The fix (getFilteredCreditSalePayments) covers both paths.
 */
class PdPrintShiftCreditSalesIsolationTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function print_only_displays_credit_sales_belonging_to_the_settlement_shift(): void
    {
        $user = User::where('business_id', $this->businessId)->firstOrFail();

        $shift1Id = $this->seedShift();
        $shift4Id = $this->seedShift();

        $locationId = DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        // Settlement is keyed to Shift 4 only
        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no'    => 'SET-PD-CS-TEST-' . uniqid(),
            'business_id'      => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date'      => now()->toDateString(),
            'location_id'      => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift'       => json_encode([$shift4Id]),
            'status'           => 1,
            'is_edit'          => 0,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $settlement = Settlement::findOrFail($settlementId);

        // pump_operator_payments that tie credit_sale_payments to shifts
        $this->seedPumpOperatorPayment([
            'payment_type'       => 'credit',
            'payment_amount'     => 15000.00,
            'shift_id'           => $shift1Id,
            'settlement_no'      => $settlement->id,
            'collection_form_no' => 'CF1-' . uniqid(),
        ]);

        $cf4No = 'CF4-' . uniqid();
        $this->seedPumpOperatorPayment([
            'payment_type'       => 'credit',
            'payment_amount'     => 25000.00,
            'shift_id'           => $shift4Id,
            'settlement_no'      => $settlement->id,
            'collection_form_no' => $cf4No,
        ]);

        // settlement_credit_sale_payments rows linked to the settlement
        DB::table('settlement_credit_sale_payments')->insert([
            $this->buildCreditSalePaymentData([
                'settlement_no'      => (string) $settlement->id,
                'amount'             => 15000.00,
                'collection_form_no' => 'CF1-' . uniqid(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]),
            $this->buildCreditSalePaymentData([
                'settlement_no'      => (string) $settlement->id,
                'amount'             => 25000.00,
                'collection_form_no' => $cf4No,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]),
        ]);

        $response = $this->actingAs($user)
            ->get("/petro/settlement-pd/print/{$settlement->id}");

        $response->assertOk();
        $response->assertSee('25,000.00');
        $response->assertDontSee('15,000.00');
    }

    private function seedShift(): int
    {
        return DB::table('petro_shifts')->insertGetId([
            'business_id'      => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status'           => 2,
            'shift_date'       => now()->toDateString(),
            'closed_time'      => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }
}
