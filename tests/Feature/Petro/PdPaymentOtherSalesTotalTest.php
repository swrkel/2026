<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PdPaymentOtherSalesTotalTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function payment_tab_totals_include_net_pumper_dashboard_other_sales_for_the_settlement_shift(): void
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');
        $storeId = (int) DB::table('stores')
            ->where('business_id', $this->businessId)
            ->value('id') ?: 1;

        if (! $locationId || ! $pumpId) {
            $this->markTestSkipped('Missing Petro location or pump fixture.');
        }

        $shiftId = $this->seedPetroShift();
        $settlementNo = 'PDST-OS-' . uniqid();

        DB::table('settlements')->insert([
            'settlement_no' => $settlementNo,
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([(string) $shiftId]),
            'total_amount' => 0,
            'status' => 0,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pump_operator_assignments')->insert([
            'business_id' => $this->businessId,
            'pump_id' => $pumpId,
            'pump_operator_id' => $this->pumpOperatorId,
            'starting_meter' => 0,
            'closing_meter' => 0,
            'date_and_time' => now(),
            'close_date_and_time' => now(),
            'status' => 'close',
            'settlement_id' => null,
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 1,
            'closed_in_settlement' => 1,
            'shift_id' => $shiftId,
            'shift_number' => 703,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pump_operator_other_sales')->insert([
            'business_id' => $this->businessId,
            'store_id' => $storeId,
            'product_id' => $this->productId,
            'price' => 20000,
            'qty' => 1,
            'balance_stock' => 0,
            'discount' => 10,
            'discount_type' => 'percentage',
            'discount_amount' => 2000,
            'sub_total' => 20000,
            'shift_id' => $shiftId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petro/settlement-pd/get-payment-tab-totals?' . http_build_query([
                'settlement_no' => $settlementNo,
            ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertSame(18000.0, (float) $response->json('other_sale_total'));
    }

    /** @test */
    public function othersale_list_merges_manual_sales_when_merge_manual_is_set(): void
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        $storeId = (int) DB::table('stores')
            ->where('business_id', $this->businessId)
            ->value('id') ?: 1;

        if (! $locationId) {
            $this->markTestSkipped('Missing Petro location.');
        }

        $shiftId = $this->seedPetroShift();
        $settlementNo = 'PDST-OS-' . uniqid();

        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => $settlementNo,
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([(string) $shiftId]),
            'total_amount' => 0,
            'status' => 1, // draft active
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('other_sales')->insert([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementId,
            'store_id' => $storeId,
            'product_id' => $this->productId,
            'price' => 5000,
            'qty' => 2,
            'balance_stock' => 50,
            'discount' => 0,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'sub_total' => 10000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson('/petro/pump-operator-payments/othersale-list?' . http_build_query([
                'shift_ids' => [$shiftId],
                'active_settlement_id' => $settlementId,
                'merge_manual' => 1,
            ]));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame(1, (int) $response->json('data.0.user_check'));
        $this->assertStringContainsString('<button', $response->json('data.0.action'));
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
