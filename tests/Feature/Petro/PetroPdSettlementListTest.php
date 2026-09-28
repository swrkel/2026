<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PetroPdSettlementListTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function pd_settlement_list_shows_finalized_pumps_hides_open_shift_drafts_and_totals_other_sales(): void
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $pumpId) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        $storeId = (int) DB::table('stores')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $storeId) {
            $storeId = 1;
        }

        $closedShiftId = $this->seedPetroShift(0, now());
        $completedSettlementId = $this->seedPdSettlement([
            'settlement_no' => 'PDST-LIST-' . uniqid(),
            'location_id' => $locationId,
            'work_shift' => json_encode([(string) $closedShiftId]),
            'total_amount' => 0,
            'status' => 0,
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 0,
            'closing_meter' => 10,
            'date_and_time' => now(),
            'close_date_and_time' => now(),
            'status' => 'close',
            'settlement_id' => null,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 1,
            'closed_in_settlement' => 1,
            'shift_id' => $closedShiftId,
            'shift_number' => 501,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $meterSaleId = $this->seedPumpOperatorMeterSale($closedShiftId, [
            'amount' => 1146,
            'balance' => 1146,
            'settlement_no' => DB::table('settlements')->where('id', $completedSettlementId)->value('settlement_no'),
        ]);

        DB::table('pump_operator_meter_sale_details')->insert([
            'sale_id' => $meterSaleId,
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'pump_id' => $pumpId,
            'received_meter' => 0,
            'new_meter' => 10,
            'sold_qty' => 10,
            'unit_price' => 114.6,
            'amount' => 1146,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pump_operator_other_sales')->insert([
            'business_id' => $this->businessId,
            'store_id' => $storeId,
            'product_id' => $this->productId,
            'price' => 2038,
            'qty' => 1,
            'balance_stock' => 0,
            'discount' => 0,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'sub_total' => 2038,
            'shift_id' => $closedShiftId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $openShiftId = $this->seedPetroShift(1, null);
        $openDraftId = $this->seedPdSettlement([
            'settlement_no' => 'PDST-OPEN-' . uniqid(),
            'location_id' => $locationId,
            'work_shift' => json_encode([(string) $openShiftId]),
            'total_amount' => 3184,
            'status' => 1,
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 0,
            'closing_meter' => 0,
            'date_and_time' => now(),
            'close_date_and_time' => null,
            'status' => 'open',
            'settlement_id' => null,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 0,
            'closed_in_settlement' => 0,
            'shift_id' => $openShiftId,
            'shift_number' => 502,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $completedRow = $this->listPdSettlementRow($completedSettlementId);
        $pumpNo = DB::table('pumps')->where('id', $pumpId)->value('pump_no');

        $this->assertStringContainsString((string) $pumpNo, (string) $completedRow['pump_nos']);
        $this->assertStringContainsString('3,184.00', (string) $completedRow['total_amount']);

        $openDraftRows = $this->listPdSettlementRows($openDraftId);
        $this->assertCount(1, $openDraftRows);
        $this->assertStringContainsString('Pending', $openDraftRows[0]['status']);
    }

    /** @test */
    public function pd_settlement_list_totals_shift_meter_sales_even_when_pd_meter_sale_is_not_linked_by_settlement_no(): void
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $pumpId) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        $storeId = (int) DB::table('stores')
            ->where('business_id', $this->businessId)
            ->value('id') ?: 1;

        $closedShiftId = $this->seedPetroShift(0, now());
        $completedSettlementId = $this->seedPdSettlement([
            'settlement_no' => 'PDST-LIST-' . uniqid(),
            'location_id' => $locationId,
            'work_shift' => json_encode([(string) $closedShiftId]),
            'total_amount' => 0,
            'status' => 0,
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 0,
            'closing_meter' => 10,
            'date_and_time' => now(),
            'close_date_and_time' => now(),
            'status' => 'close',
            'settlement_id' => null,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 1,
            'closed_in_settlement' => 1,
            'shift_id' => $closedShiftId,
            'shift_number' => 503,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $meterSaleId = $this->seedPumpOperatorMeterSale($closedShiftId, [
            'amount' => 764000,
            'balance' => 764000,
            'settlement_no' => '',
        ]);

        DB::table('pump_operator_meter_sale_details')->insert([
            'sale_id' => $meterSaleId,
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'pump_id' => $pumpId,
            'received_meter' => 794588.70,
            'new_meter' => 796588.70,
            'sold_qty' => 2000,
            'unit_price' => 382,
            'amount' => 764000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pump_operator_other_sales')->insert([
            'business_id' => $this->businessId,
            'store_id' => $storeId,
            'product_id' => $this->productId,
            'price' => 2200,
            'qty' => 1,
            'balance_stock' => 0,
            'discount' => 0,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'sub_total' => 2200,
            'shift_id' => $closedShiftId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $completedRow = $this->listPdSettlementRow($completedSettlementId);

        $this->assertStringContainsString('766,200.00', (string) $completedRow['total_amount']);
    }

    /** @test */
    public function pd_settlement_list_hides_closed_assignment_draft_before_first_save(): void
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $pumpId) {
            $this->markTestSkipped("No pumps for business {$this->businessId}.");
        }

        $closedShiftId = $this->seedPetroShift(2, now());
        $draftSettlementId = $this->seedPdSettlement([
            'settlement_no' => 'PDST-DRAFT-' . uniqid(),
            'location_id' => $locationId,
            'work_shift' => json_encode([(string) $closedShiftId]),
            'total_amount' => 110625,
            'status' => 1,
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 0,
            'closing_meter' => 10,
            'date_and_time' => now(),
            'close_date_and_time' => now(),
            'status' => 'close',
            'settlement_id' => $draftSettlementId,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 1,
            'closed_in_settlement' => 1,
            'shift_id' => $closedShiftId,
            'shift_number' => 601,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $draftRows = $this->listPdSettlementRows($draftSettlementId);

        $this->assertCount(1, $draftRows);
        $this->assertStringContainsString('Pending', $draftRows[0]['status']);
    }

    private function listPdSettlementRow(int $settlementId): array
    {
        $rows = $this->listPdSettlementRows($settlementId);

        $this->assertCount(1, $rows);

        return $rows[0];
    }

    private function listPdSettlementRows(int $settlementId): array
    {
        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petropd/list-pd-settlement?' . http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'settlement_no' => $settlementId,
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ]));

        $response->assertOk();

        return $response->json('data') ?? [];
    }

    private function seedPdSettlement(array $overrides = []): int
    {
        return DB::table('settlements')->insertGetId(array_merge([
            'settlement_no' => 'PDST-LIST-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $overrides['location_id'] ?? 1,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([]),
            'note' => null,
            'total_amount' => 0,
            'status' => 0,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function seedPetroShift(int $status, $closedTime): int
    {
        $data = [
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => $status,
            'shift_date' => now(),
            'closed_time' => $closedTime,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('petro_shifts', 'work_shift_id')) {
            $data['work_shift_id'] = null;
        }

        return DB::table('petro_shifts')->insertGetId($data);
    }

    private function seedPumpOperatorMeterSale(int $shiftId, array $overrides = []): int
    {
        $data = array_merge([
            'business_id' => $this->businessId,
            'date_time' => now(),
            'pump_operator_id' => $this->pumpOperatorId,
            'amount' => 1146,
            'deposited' => 0,
            'balance' => 1146,
            'shift_id' => $shiftId,
            'collection_form_no' => 'LIST-' . uniqid(),
            'p_o_payment_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        if (Schema::hasColumn('pump_operator_meter_sales', 'source')) {
            $data['source'] = 'closing';
        }

        if (Schema::hasColumn('pump_operator_meter_sales', 'testing_qty')) {
            $data['testing_qty'] = 0;
        }

        return DB::table('pump_operator_meter_sales')->insertGetId($data);
    }
}
