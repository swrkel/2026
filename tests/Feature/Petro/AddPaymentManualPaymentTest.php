<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\Settlement;

class AddPaymentManualPaymentTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function cash_add_button_can_save_a_manual_payment_without_pump_payment_id(): void
    {
        $settlement = $this->seedSettlement();

        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson('/petro/settlement/payment/save-cash-payment', [
                'settlement_no' => $settlement->settlement_no,
                'customer_id' => $this->contactId,
                'amount' => 125.50,
                'note' => 'manual cash add button',
                'is_edit' => 1,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(1, DB::table('settlement_cash_payments')
            ->where('business_id', $this->businessId)
            ->where('settlement_no', (string) $settlement->id)
            ->where('customer_id', $this->contactId)
            ->whereNull('pump_payment_id')
            ->count());
    }

    /** @test */
    public function card_add_button_can_save_a_manual_payment_without_pump_payment_id(): void
    {
        $settlement = $this->seedSettlement();

        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->postJson('/petro/settlement/payment/save-card-payment', [
                'settlement_no' => $settlement->settlement_no,
                'customer_id' => $this->contactId,
                'amount' => 120,
                'card_type' => 1,
                'card_number' => '11111',
                'slip_no' => '',
                'note' => 'manual card add button',
                'is_edit' => 1,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(1, DB::table('settlement_card_payments')
            ->where('business_id', $this->businessId)
            ->where('settlement_no', (string) $settlement->id)
            ->where('customer_id', $this->contactId)
            ->whereNull('pump_payment_id')
            ->count());
    }

    private function seedSettlement(): Settlement
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        $id = DB::table('settlements')->insertGetId([
            'settlement_no' => 'PDST-MANUAL-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift' => json_encode([]),
            'status' => 0,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Settlement::findOrFail($id);
    }
}
