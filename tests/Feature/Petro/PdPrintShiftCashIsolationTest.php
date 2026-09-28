<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCashPayment;

class PdPrintShiftCashIsolationTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function print_and_preview_only_display_cash_payments_related_to_selected_settlement_shifts(): void
    {
        $user = User::where('business_id', $this->businessId)->firstOrFail();

        $shift1Id = $this->seedShift();
        $shift4Id = $this->seedShift();

        $locationId = DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        // Create one draft settlement for the operator, date, and location
        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no'    => 'SET-PD-TEST-' . uniqid(),
            'business_id'      => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date'      => now()->toDateString(),
            'location_id'      => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift'       => json_encode([$shift4Id]),
            'status'           => 1, // draft
            'is_edit'          => 0,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $settlement = Settlement::findOrFail($settlementId);

        // Shift 1 payment (Shift 1)
        $payment1Id = $this->seedPumpOperatorPayment([
            'payment_type'   => 'cash',
            'payment_amount' => 1000.00,
            'shift_id'       => $shift1Id,
            'settlement_no'  => $settlement->id,
        ]);

        // Shift 4 payment (Shift 4)
        $payment4Id = $this->seedPumpOperatorPayment([
            'payment_type'   => 'cash',
            'payment_amount' => 2000.00,
            'shift_id'       => $shift4Id,
            'settlement_no'  => $settlement->id,
        ]);

        // Create SettlementCashPayment rows linked to the settlement
        DB::table('settlement_cash_payments')->insert([
            [
                'settlement_no'       => (string) $settlement->id,
                'business_id'         => $this->businessId,
                'customer_id'         => $this->contactId,
                'amount'              => 1000.00,
                'customer_payment_id' => null,
                'pump_payment_id'     => $payment1Id,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'settlement_no'       => (string) $settlement->id,
                'business_id'         => $this->businessId,
                'customer_id'         => $this->contactId,
                'amount'              => 2000.00,
                'customer_payment_id' => null,
                'pump_payment_id'     => $payment4Id,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]
        ]);

        // Call the payment preview endpoint for the settlement (uses AddPaymentController@preview)
        $previewResponse = $this->actingAs($user)
            ->get("/petro/settlement/payment/preview/{$settlement->id}");

        $previewResponse->assertOk();
        $previewResponse->assertDontSee('1,000.00');
        $previewResponse->assertSee('2,000.00');

        // Call the print endpoint for the settlement (uses SettlementPDController@print)
        $printResponse = $this->actingAs($user)
            ->get("/petro/settlement-pd/print/{$settlement->id}");

        $printResponse->assertOk();
        $printResponse->assertDontSee('1,000.00');
        $printResponse->assertSee('2,000.00');
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
