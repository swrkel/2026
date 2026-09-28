<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShiftSummaryFiltersTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_shift_summary_filters_work_correctly(): void
    {
        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail());
        
        $pump = DB::table('pumps')->where('business_id', $this->businessId)->first();
        if (!$pump) {
            $this->markTestSkipped('No pumps found.');
        }

        // Seed a shift
        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => '2026-05-20',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignmentId = DB::table('pump_operator_assignments')->insertGetId([
            'business_id' => $this->businessId,
            'pump_id' => $pump->id,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 100,
            'closing_meter' => 150,
            'status' => 'close',
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'shift_id' => $shiftId,
            'shift_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $entryData = [
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'pumper_assignment_id' => $assignmentId,
            'pump_id' => $pump->id,
            'date' => '2026-05-20',
            'starting_meter' => 100,
            'closing_meter' => 150,
            'testing_ltr' => 0,
            'sold_ltr' => 50,
            'amount' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('pumper_day_entries', 'shift_id')) {
            $entryData['shift_id'] = $shiftId;
        }
        if (Schema::hasColumn('pumper_day_entries', 'pump_no')) {
            $entryData['pump_no'] = $pump->pump_no;
        }

        DB::table('pumper_day_entries')->insert($entryData);

        // Seed pump operator payment
        DB::table('pump_operator_payments')->insert([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'payment_type' => 'cash',
            'payment_amount' => 400.00,
            'shift_id' => $shiftId,
            'date_and_time' => '2026-05-20 10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Access route with correct date filter and verify values
        $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petro/pump-operators/shift-summary?' . http_build_query([
                'start_date' => '2026-05-20',
                'end_date' => '2026-05-20',
                'location_id' => '',
                'pump_operator_id' => $this->pumpOperatorId,
                'pump_id' => $pump->id,
            ]));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->pumpOperatorId, $data[0]['pump_operator_id']);
        $this->assertStringContainsString('400.00', $data[0]['cash']);
        $this->assertStringContainsString('-100.00', $data[0]['difference']);

        // Test payment_method filter
        $responsePaymentMatch = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petro/pump-operators/shift-summary?' . http_build_query([
                'start_date' => '2026-05-20',
                'end_date' => '2026-05-20',
                'payment_method' => 'cash',
            ]));
        $responsePaymentMatch->assertOk();
        $this->assertCount(1, $responsePaymentMatch->json('data'));

        $responsePaymentNoMatch = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petro/pump-operators/shift-summary?' . http_build_query([
                'start_date' => '2026-05-20',
                'end_date' => '2026-05-20',
                'payment_method' => 'card',
            ]));
        $responsePaymentNoMatch->assertOk();
        $this->assertCount(0, $responsePaymentNoMatch->json('data'));

        // Test difference filter
        $responseDiffMatch = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petro/pump-operators/shift-summary?' . http_build_query([
                'start_date' => '2026-05-20',
                'end_date' => '2026-05-20',
                'difference' => 'negative',
            ]));
        $responseDiffMatch->assertOk();
        $this->assertCount(1, $responseDiffMatch->json('data'));

        $responseDiffNoMatch = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petro/pump-operators/shift-summary?' . http_build_query([
                'start_date' => '2026-05-20',
                'end_date' => '2026-05-20',
                'difference' => 'positive',
            ]));
        $responseDiffNoMatch->assertOk();
        $this->assertCount(0, $responseDiffNoMatch->json('data'));

        // Access route with non-matching date filter (should return 0 rows)
        $responseEmpty = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petro/pump-operators/shift-summary?' . http_build_query([
                'start_date' => '2026-05-21',
                'end_date' => '2026-05-21',
                'location_id' => '',
                'pump_operator_id' => $this->pumpOperatorId,
                'pump_id' => $pump->id,
            ]));
        $responseEmpty->assertOk();
        $this->assertCount(0, $responseEmpty->json('data'));
    }

    /** @test */
    public function test_shift_summary_view_has_dynamic_ids(): void
    {
        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail());
        
        $response = $this->get('/petropd/pd-operators');
        $response->assertOk();
        $response->assertSee('id="shift_summary_total_sale_val"', false);
        $response->assertSee('id="shift_summary_total_payments_val"', false);
        $response->assertSee('id="shift_summary_balance_to_settle_val"', false);
    }
}
