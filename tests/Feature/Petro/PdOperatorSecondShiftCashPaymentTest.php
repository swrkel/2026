<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\PumpOperatorPayment;
use Modules\Petro\Services\SettlementPaymentReconciler;

class PdOperatorSecondShiftCashPaymentTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_retains_cash_sale_amount_in_second_shift_settlement(): void
    {
        $user = User::where('business_id', $this->businessId)->firstOrFail();

        // Update package_details in system subscription to include stock_adjustment key
        DB::connection('system')->table('subscriptions')
            ->where('business_id', $this->businessId)
            ->update([
                'package_details' => json_encode([
                    'petro_pd_module' => 1,
                    'stock_adjustment' => 1,
                ])
            ]);

        SettlementPaymentReconciler::withBypass(function () {
            // 1. Create a first settlement for shift 101
            $settlement1 = Settlement::create([
                'settlement_no' => 'PDST-TEST-SHIFT1-' . uniqid(),
                'business_id' => $this->businessId,
                'transaction_date' => now()->toDateString(),
                'finish_date' => now()->toDateString(),
                'pump_operator_id' => $this->pumpOperatorId,
                'work_shift' => json_encode([101]),
                'status' => 1,
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subHours(4),
            ]);

            // 2. Create a second settlement (second shift) for shift 102
            $settlement2 = Settlement::create([
                'settlement_no' => 'PDST-TEST-SHIFT2-' . uniqid(),
                'business_id' => $this->businessId,
                'transaction_date' => now()->toDateString(),
                'finish_date' => now()->toDateString(),
                'pump_operator_id' => $this->pumpOperatorId,
                'work_shift' => json_encode([102]),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3. Create a PumpOperatorPayment for shift 102 (Cash type)
            $pop = PumpOperatorPayment::create([
                'business_id' => $this->businessId,
                'pump_operator_id' => $this->pumpOperatorId,
                'shift_id' => 102,
                'payment_type' => 'cash',
                'payment_amount' => 450.00,
                'is_used' => 1,
                'settlement_no' => $settlement2->id,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Create a SettlementCashPayment for settlement 2, linked to the pump payment via pump_payment_id
            $scp = SettlementCashPayment::create([
                'settlement_no' => $settlement2->id,
                'business_id' => $this->businessId,
                'customer_id' => $this->contactId,
                'amount' => 450.00,
                'note' => 'Second shift cash payment test',
                'pump_payment_id' => $pop->id,
                'customer_payment_id' => null, // Left null as per actual saveCashPayment implementation
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Link pump payment parent_id
            $pop->update(['parent_id' => $scp->id]);

            $this->settlement2Id = $settlement2->id;
            $this->scpPumpPaymentId = $scp->pump_payment_id;
        });

        // 5. Call AddPaymentController@create for settlement 2, simulating loading the second shift payments modal
        $url = action('\Modules\Petro\Http\Controllers\AddPaymentController@create', [
            'type' => 'settlement_pd',
            'active_settlement_id' => $this->settlement2Id,
            'operator_id' => $this->pumpOperatorId,
            'shift_ids' => '102',
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->get($url);

        $response->assertOk();

        // 6. Assert that the settlement cash payments contains our seeded payment of 450.00 with correct shift ID
        $viewPayments = $response->viewData('settlement_cash_payments');
        $this->assertNotNull($viewPayments, 'settlement_cash_payments variable was not passed to the view');
        
        $hasPayment = collect($viewPayments)->contains(function ($payment) {
            return (float) $payment->amount === 450.00 
                && (int) $payment->pump_payment_id === $this->scpPumpPaymentId
                && (int) $payment->payment_shift_id === 102;
        });

        $this->assertTrue($hasPayment, 'The added cash payment was not loaded/found with correct shift ID in the second shift settlement');
    }
}
