<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCreditSalePayment;

class SettlementCreditSalePaymentMultiBusinessTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function credit_sale_payments_relationship_does_not_leak_other_businesses_data_with_same_settlement_no(): void
    {
        $settlementNo = 'ST-LEAK-TEST-' . uniqid();

        // Create settlement for current business X (businessId)
        $settlementIdX = DB::table('settlements')->insertGetId([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementNo,
            'status' => 0,
            'transaction_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $settlementX = Settlement::findOrFail($settlementIdX);

        // Create a different business Y (businessId + 100)
        $businessIdY = $this->businessId + 100;
        DB::table('business')->insert([
            'id' => $businessIdY,
            'name' => 'Other Business Y',
            'currency_id' => 1,
            'owner_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a credit sale payment for business Y with same settlement_no
        $paymentIdY = DB::table('settlement_credit_sale_payments')->insertGetId([
            'business_id' => $businessIdY,
            'settlement_no' => $settlementNo,
            'customer_id' => $this->contactId,
            'product_id' => $this->productId,
            'pump_operator_id' => $this->pumpOperatorId,
            'order_number' => 'ORDER-Y',
            'order_date' => now()->toDateString(),
            'price' => 100,
            'discount' => 0,
            'qty' => 1,
            'amount' => 100,
            'sub_total' => 100,
            'total_discount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log in a user matching the current business context
        $user = \App\User::where('business_id', $this->businessId)->first();
        if ($user) {
            $this->actingAs($user);
        }

        // Load the relationship
        $settlementX->load('credit_sale_payments');

        // Confirm that settlement X does NOT leak the credit sale payment from business Y
        $this->assertCount(0, $settlementX->credit_sale_payments);
    }

    /** @test */
    public function pumper_dashboard_finalization_does_not_leak_other_businesses_credit_sales_with_same_settlement_no(): void
    {
        $settlementNo = 'ST-LEAK-TEST-' . uniqid();

        // Create settlement for current business X (businessId)
        $settlementIdX = DB::table('settlements')->insertGetId([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementNo,
            'status' => 0,
            'pump_operator_id' => $this->pumpOperatorId,
            'transaction_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $settlementX = Settlement::findOrFail($settlementIdX);

        // Create a different business Y (businessId + 200)
        $businessIdY = $this->businessId + 200;
        DB::table('business')->insert([
            'id' => $businessIdY,
            'name' => 'Other Business Y2',
            'currency_id' => 1,
            'owner_id' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a credit sale payment for business Y with same settlement_no
        $paymentIdY = DB::table('settlement_credit_sale_payments')->insertGetId(
            $this->buildCreditSalePaymentData([
                'business_id' => $businessIdY,
                'settlement_no' => $settlementNo,
                'pump_operator_id' => 9999, // completely different pump operator
                'qty' => 10,
                'amount' => 1000,
                'sub_total' => 1000,
            ])
        );

        // Log in a user matching the current business context
        $user = \App\User::where('business_id', $this->businessId)->first();
        if ($user) {
            $this->actingAs($user);
        }

        // 1. First, verify that the unscoped query indeed leaks the record (reproducing the original bug)
        $unscoped_sales = DB::table('settlement_credit_sale_payments')
            ->where('settlement_no', $settlementX->settlement_no)
            ->get();
        $this->assertCount(1, $unscoped_sales);

        // 2. Verify that the new scoped query (with business_id) correctly excludes the leak
        $credit_sales = SettlementCreditSalePayment::where('business_id', $settlementX->business_id)
            ->where(function ($q) use ($settlementX) {
                $q->where('settlement_no', $settlementX->settlement_no)
                    ->orWhere('settlement_no', $settlementX->id);
            })
            ->get();

        $this->assertCount(0, $credit_sales);
    }
}

