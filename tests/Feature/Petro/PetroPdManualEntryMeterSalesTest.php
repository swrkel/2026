<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetroPdManualEntryMeterSalesTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function manual_entry_meter_sales_do_not_append_zero_day_entry_fallback_when_real_details_exist(): void
    {
        $pump = DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->whereNotNull('product_id')
            ->first();
        if (! $pump) {
            $this->markTestSkipped("No pumps with product_id for business {$this->businessId}.");
        }

        $shiftId = $this->seedPetroShift();
        $assignmentId = $this->seedAssignment((int) $pump->id, $shiftId);
        $settlementNo = 'PDST-ME-' . uniqid();
        $settlementId = $this->seedPdSettlement((int) $pump->id, $shiftId, $settlementNo);
        $saleId = $this->seedPumpOperatorMeterSale($shiftId, $settlementNo);

        DB::table('pump_operator_meter_sale_details')->insert([
            'sale_id' => $saleId,
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'pump_id' => (int) $pump->id,
            'received_meter' => 794588.70,
            'new_meter' => 796588.70,
            'sold_qty' => 2000,
            'unit_price' => 382,
            'amount' => 764000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pumper_day_entries')->insert($this->pumperDayEntryData([
            'pumper_assignment_id' => $assignmentId,
            'pump_id' => (int) $pump->id,
            'pump_no' => $pump->pump_no,
            'starting_meter' => 794588.70,
            'closing_meter' => 794588.70,
            'sold_ltr' => 0,
            'testing_ltr' => 0,
            'amount' => 0,
        ]));

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petropd/get-manual-entry-meter-sales?' . http_build_query([
                'pump_operator_id' => $this->pumpOperatorId,
                'shift_id' => $shiftId,
                'active_settlement_id' => $settlementId,
                'settlement_no' => $settlementNo,
            ]));

        $response->assertOk()->assertJson(['success' => true]);

        $rows = $response->json('rows');
        $this->assertCount(1, $rows);
        $this->assertSame('2,000.00', $rows[0]['sold_qty']);
        $this->assertSame('764,000.00', $rows[0]['after_discount']);
    }

    /** @test */
    public function manual_entry_meter_sales_do_not_return_zero_day_entry_fallback_rows(): void
    {
        $pump = DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->whereNotNull('product_id')
            ->first();
        if (! $pump) {
            $this->markTestSkipped("No pumps with product_id for business {$this->businessId}.");
        }

        $shiftId = $this->seedPetroShift();
        $assignmentId = $this->seedAssignment((int) $pump->id, $shiftId);
        $settlementNo = 'PDST-ME-' . uniqid();
        $settlementId = $this->seedPdSettlement((int) $pump->id, $shiftId, $settlementNo);

        DB::table('pumper_day_entries')->insert($this->pumperDayEntryData([
            'pumper_assignment_id' => $assignmentId,
            'pump_id' => (int) $pump->id,
            'pump_no' => $pump->pump_no,
            'starting_meter' => 794588.70,
            'closing_meter' => 794588.70,
            'sold_ltr' => 0,
            'testing_ltr' => 0,
            'amount' => 0,
        ]));

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petropd/get-manual-entry-meter-sales?' . http_build_query([
                'pump_operator_id' => $this->pumpOperatorId,
                'shift_id' => $shiftId,
                'active_settlement_id' => $settlementId,
                'settlement_no' => $settlementNo,
            ]));

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertCount(0, $response->json('rows'));
    }

    private function seedPetroShift(): int
    {
        $data = [
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('petro_shifts', 'work_shift_id')) {
            $data['work_shift_id'] = null;
        }

        return DB::table('petro_shifts')->insertGetId($data);
    }

    private function seedAssignment(int $pumpId, int $shiftId): int
    {
        return DB::table('pump_operator_assignments')->insertGetId([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 794588.70,
            'closing_meter' => 796588.70,
            'date_and_time' => now(),
            'close_date_and_time' => now(),
            'status' => 'close',
            'settlement_id' => null,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 1,
            'closed_in_settlement' => 0,
            'shift_id' => $shiftId,
            'shift_number' => 801,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedPdSettlement(int $pumpId, int $shiftId, string $settlementNo): int
    {
        $locationId = (int) DB::table('pumps')->where('id', $pumpId)->value('location_id');
        if (! $locationId) {
            $locationId = (int) DB::table('business_locations')->where('business_id', $this->businessId)->value('id');
        }

        return DB::table('settlements')->insertGetId([
            'settlement_no' => $settlementNo,
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([(string) $shiftId]),
            'note' => null,
            'total_amount' => 764000,
            'status' => 1,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedPumpOperatorMeterSale(int $shiftId, string $settlementNo): int
    {
        $data = [
            'business_id' => $this->businessId,
            'date_time' => now(),
            'pump_operator_id' => $this->pumpOperatorId,
            'amount' => 764000,
            'deposited' => 0,
            'balance' => 764000,
            'shift_id' => $shiftId,
            'collection_form_no' => 'ME-' . uniqid(),
            'p_o_payment_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('pump_operator_meter_sales', 'settlement_no')) {
            $data['settlement_no'] = $settlementNo;
        }

        if (Schema::hasColumn('pump_operator_meter_sales', 'source')) {
            $data['source'] = 'closing';
        }

        if (Schema::hasColumn('pump_operator_meter_sales', 'testing_qty')) {
            $data['testing_qty'] = 0;
        }

        return DB::table('pump_operator_meter_sales')->insertGetId($data);
    }

    private function pumperDayEntryData(array $overrides): array
    {
        $data = array_merge([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'pumper_assignment_id' => null,
            'pump_id' => null,
            'date' => now()->toDateString(),
            'starting_meter' => 0,
            'closing_meter' => 0,
            'testing_ltr' => 0,
            'sold_ltr' => 0,
            'amount' => 0,
            'settlement_no' => null,
            'settlement_datetime' => null,
            'settlement_added_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        foreach (['shift_id' => $overrides['shift_id'] ?? null, 'closed_in_settlement' => 0, 'pump_no' => $overrides['pump_no'] ?? '', 'time' => now()->format('H:i:s')] as $column => $value) {
            if (Schema::hasColumn('pumper_day_entries', $column)) {
                $data[$column] = $value;
            } else {
                unset($data[$column]);
            }
        }

        return $data;
    }
}
