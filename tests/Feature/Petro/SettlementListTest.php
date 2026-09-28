<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettlementListTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function direct_settlement_list_shows_traditional_settlement_when_linked_pd_settlement_is_still_pending(): void
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');

        if (! $locationId || ! $pumpId) {
            $this->markTestSkipped('No location or pump available for settlement list test.');
        }

        $shiftId = $this->seedPetroShift();
        $traditionalSettlementId = $this->seedSettlement([
            'settlement_no' => 'ST-LIST-' . uniqid(),
            'location_id' => $locationId,
            'total_amount' => 0,
            'status' => 1,
        ]);

        DB::table('meter_sales')->insert([
            'business_id' => $this->businessId,
            'settlement_no' => $traditionalSettlementId,
            'pump_id' => $pumpId,
            'product_id' => $this->productId,
            'shift_id' => $shiftId,
            'starting_meter' => 0,
            'closing_meter' => 10,
            'price' => 10,
            'qty' => 10,
            'sub_total' => 100,
            'discount_amount' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pendingPdSettlementId = $this->seedSettlement([
            'settlement_no' => 'PDST-LIST-' . uniqid(),
            'location_id' => $locationId,
            'work_shift' => json_encode([(string) $shiftId]),
            'total_amount' => 100,
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
            'settlement_id' => $pendingPdSettlementId,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 1,
            'closed_in_settlement' => 0,
            'shift_id' => $shiftId,
            'shift_number' => 341,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rows = $this->listSettlementRows($traditionalSettlementId);

        $this->assertCount(1, $rows);
        $this->assertSame('ST-LIST-', substr($rows[0]['settlement_no'], 0, 8));
    }

    private function listSettlementRows(int $settlementId): array
    {
        $this->enablePetroModuleForRequest();

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
                'currency' => $this->currencySessionData(),
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petro/settlement?' . http_build_query([
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

    private function enablePetroModuleForRequest(): void
    {
        if (! Schema::connection('system')->hasTable('subscriptions')) {
            return;
        }

        DB::connection('system')->table('subscriptions')->insert([
            'business_id' => $this->businessId,
            'package_id' => 1,
            'start_date' => now()->subDay()->toDateString(),
            'trial_end_date' => null,
            'end_date' => now()->addYears(2)->toDateString(),
            'package_price' => 0,
            'package_details' => json_encode([
                'enable_petro_module' => 1,
                'petro_pd_module' => 1,
            ]),
            'created_id' => $this->userId,
            'paid_via' => null,
            'payment_transaction_id' => null,
            'status' => 'approved',
            'module_activation_details' => json_encode([]),
            'customer_credit_notification_type' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedSettlement(array $overrides = []): int
    {
        return DB::table('settlements')->insertGetId(array_merge([
            'settlement_no' => 'ST-LIST-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => null,
            'location_id' => $overrides['location_id'] ?? 1,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([]),
            'note' => null,
            'total_amount' => 0,
            'status' => 1,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
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
}
