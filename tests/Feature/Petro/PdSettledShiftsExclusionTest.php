<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Modules\Petro\Http\Controllers\SettlementPDController;

class PdSettledShiftsExclusionTest extends PetroTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::define('petro_pd.delete_settlement', fn () => true);
        Gate::define('petro_pd.edit_settlement', fn () => true);
    }

    /** @test */
    public function test_deleted_settlement_resets_linked_assignments(): void
    {
        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => 'PDST-DEL-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => 1,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([(string) $shiftId]),
            'note' => null,
            'total_amount' => 1000.00,
            'status' => 1,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignmentId = DB::table('pump_operator_assignments')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'pump_id' => 1,
            'shift_id' => $shiftId,
            'settlement_id' => $settlementId,
            'closed_in_settlement' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = \App\User::findOrFail($this->userId);
        $this->actingAs($user);

        // Setup session for request to prevent NullPointerException in business.id / business.ref_no_prefixes
        $request = Request::create("/petro/settlement-pd/{$settlementId}", 'DELETE');
        $request->setLaravelSession(session());
        app()->instance('request', $request);

        $response = app(SettlementPDController::class)->destroy($settlementId);
        $this->assertTrue($response['success']);

        // Assert assignments are reset
        $assignment = DB::table('pump_operator_assignments')->where('id', $assignmentId)->first();
        $this->assertNull($assignment->settlement_id);
        $this->assertEquals(0, $assignment->closed_in_settlement);
    }

    /** @test */
    public function test_dropdown_options_exclude_finalized_shifts_but_include_current_shift(): void
    {
        // Seed three shifts
        $shift1 = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shift2 = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shift3 = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Settlement A (Draft) - current settlement being updated
        $settlementAId = DB::table('settlements')->insertGetId([
            'settlement_no' => 'PDST-A-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => 1,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([(string) $shift1]),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Settlement B (Finalized/Completed)
        $settlementBId = DB::table('settlements')->insertGetId([
            'settlement_no' => 'PDST-B-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => 1,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([(string) $shift2]),
            'status' => 0, // finalized
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create assignments
        DB::table('pump_operator_assignments')->insert([
            [
                'business_id' => $this->businessId,
                'pump_operator_id' => $this->pumpOperatorId,
                'pump_id' => 1,
                'shift_id' => $shift1,
                'shift_number' => 'SHIFT111',
                'status' => 'close',
                'settlement_id' => $settlementAId,
                'closed_in_settlement' => 1,
                'close_date_and_time' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => $this->businessId,
                'pump_operator_id' => $this->pumpOperatorId,
                'pump_id' => 1,
                'shift_id' => $shift2,
                'shift_number' => 'SHIFT222',
                'status' => 'close',
                'settlement_id' => $settlementBId,
                'closed_in_settlement' => 1,
                'close_date_and_time' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => $this->businessId,
                'pump_operator_id' => $this->pumpOperatorId,
                'pump_id' => 1,
                'shift_id' => $shift3,
                'shift_number' => 'SHIFT333',
                'status' => 'close',
                'settlement_id' => null,
                'closed_in_settlement' => 0,
                'close_date_and_time' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $user = \App\User::findOrFail($this->userId);
        $this->actingAs($user);

        $request = Request::create("/petro/settlement-pd/{$settlementAId}", 'PUT', [
            'work_shift' => [$shift1],
            'transaction_date' => now()->toDateString(),
            'pump_operator_id' => $this->pumpOperatorId,
            'location_id' => 1,
            'source' => 'petro_pd',
        ]);
        $request->setLaravelSession(session());
        app()->instance('request', $request);

        $response = app(SettlementPDController::class)->update($request, $settlementAId);
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['success']);
        
        $optionHtml = $result['optionHtml'];

        // Assert that the current shift (SHIFT111) is included in the dropdown options
        $this->assertStringContainsString('SHIFT111', $optionHtml);

        // Assert that the finalized/completed shift (SHIFT222) is EXCLUDED from the dropdown options
        $this->assertStringNotContainsString('SHIFT222', $optionHtml);

        // Assert that the unassigned shift (SHIFT333) is included in the dropdown options
        $this->assertStringContainsString('SHIFT333', $optionHtml);
    }
}
