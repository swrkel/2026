<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class DailyReportSplitColumnsTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function daily_report_correctly_aggregates_cash_deposits_and_customer_loans_for_pump_operators(): void
    {
        $user = User::find($this->userId);
        $business = DB::table('business')->where('id', $this->businessId)->first();
        
        $this->withSession([
            'business.id' => $this->businessId,
            'business.currency_id' => $business->currency_id ?? 1,
            'user.business_id' => $this->businessId,
            'user.id' => $this->userId,
        ]);
        
        $this->actingAs($user);

        // Get location and operator
        $location = DB::table('business_locations')->where('business_id', $this->businessId)->first();
        $locationId = $location ? $location->id : null;

        // Create a settlement
        $settlementId = DB::table('settlements')->insertGetId([
            'business_id' => $this->businessId,
            'location_id' => $locationId,
            'pump_operator_id' => $this->pumpOperatorId,
            'settlement_no' => 'SETTLE-TST-123',
            'status' => 'final',
            'transaction_date' => now()->toDateString(),
            'total_amount' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert a cash deposit linked to the settlement
        DB::table('settlement_cash_deposits')->insert([
            'settlement_no' => $settlementId,
            'business_id' => $this->businessId,
            'amount' => 125.50,
            'bank_id' => 1,
            'account_no' => '12345',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert a customer loan linked to the settlement
        DB::table('settlement_customer_loans')->insert([
            'settlement_no' => $settlementId,
            'business_id' => $this->businessId,
            'customer_id' => $this->contactId,
            'amount' => 250.75,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get daily report
        $startDate = now()->toDateString();
        $endDate = now()->toDateString();
        
        $response = $this->get("/reports/daily-report?print_only=true&start_date={$startDate}&end_date={$endDate}");
        $response->assertStatus(200);

        $pumpOperatorSales = $response->original->getData()['pump_operator_sales'] ?? collect();
        $this->assertNotEmpty($pumpOperatorSales);

        $targetOperatorSales = $pumpOperatorSales->first(function ($item) {
            return $item->pump_operator_id == $this->pumpOperatorId;
        });

        $this->assertNotNull($targetOperatorSales, "Pump operator sales not found for ID: {$this->pumpOperatorId}");
        
        // Assert they are not zero and match the expected values
        $this->assertEquals(125.50, $targetOperatorSales->deposit_total);
        $this->assertEquals(250.75, $targetOperatorSales->customer_loan_total);
    }
}
