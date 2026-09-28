<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class RealtimeCreditSaleDateNormalizationTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function realtime_credit_sale_accepts_display_order_date_when_removing_placeholder_accounting(): void
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');

        if (! $locationId || ! $pumpId) {
            $this->markTestSkipped("Missing location or pump for business {$this->businessId}.");
        }

        DB::table('pump_operators')
            ->where('id', $this->pumpOperatorId)
            ->update(['location_id' => $locationId]);

        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 1,
            'shift_date' => now(),
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
            'status' => 'open',
            'assigned_by' => $this->userId,
            'is_confirmed' => 1,
            'confirmed_at' => now(),
            'is_manually_closed' => 0,
            'shift_id' => $shiftId,
            'shift_number' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderNumber = 'RT-DATE-' . uniqid();
        DB::table('transactions')->insert([
            'business_id' => $this->businessId,
            'contact_id' => $this->contactId,
            'location_id' => $locationId,
            'type' => 'sell',
            'status' => 'final',
            'payment_status' => 'due',
            'final_total' => 796,
            'transaction_date' => '2026-05-20 08:00:00',
            'created_by' => $this->userId,
            'ref_no' => $orderNumber,
            'is_credit_sale' => 1,
            'is_settlement' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->postJson('/petro/pump-operator-pmts/save-credit', [
                'pump_operator_id' => $this->pumpOperatorId,
                'credit_data' => [[
                    'customer_id' => $this->contactId,
                    'product_id' => $this->productId,
                    'order_number' => $orderNumber,
                    'order_date' => '05/20/2026',
                    'price' => 398,
                    'unit_discount' => 0,
                    'qty' => 2,
                    'amount' => 796,
                    'sub_total' => 796,
                    'total_discount' => 0,
                    'outstanding' => 0,
                    'credit_limit' => 0,
                    'customer_reference' => 'DATE-' . uniqid(),
                    'note' => 'date normalization regression',
                ]],
            ]);

        $response->assertOk()->assertJson(['success' => true]);
    }
}
