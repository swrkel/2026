<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Modules\Petro\Http\Controllers\SettlementPDController;

class PdAddPaymentShiftChangeTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_shift_change_unlinks_old_payments_and_links_new_payments(): void
    {
        // 1. Seed two shifts
        $shiftA = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shiftB = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'status' => 2,
            'shift_date' => now(),
            'closed_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $settlementNo = 'PDST-TEST-' . uniqid();
        
        // 2. Seed settlement initially linked to Shift A
        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => $settlementNo,
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => 1,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([(string) $shiftA]),
            'note' => null,
            'total_amount' => 1000.00,
            'status' => 1,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Seed PumpOperatorAssignments for both shifts
        DB::table('pump_operator_assignments')->insert([
            [
                'business_id' => $this->businessId,
                'pump_operator_id' => $this->pumpOperatorId,
                'pump_id' => 1,
                'shift_id' => $shiftA,
                'settlement_id' => $settlementId,
                'closed_in_settlement' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => $this->businessId,
                'pump_operator_id' => $this->pumpOperatorId,
                'pump_id' => 1,
                'shift_id' => $shiftB,
                'settlement_id' => null,
                'closed_in_settlement' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // 4. Seed payments for Shift A (already linked)
        $popA = DB::table('pump_operator_payments')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'payment_type' => 'card',
            'payment_amount' => 500.00,
            'collection_form_no' => 'COL-A',
            'shift_id' => $shiftA,
            'is_used' => 1,
            'settlement_no' => $settlementId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cardA = DB::table('daily_cards')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_no' => 'COL-A',
            'amount' => 500.00,
            'card_type' => 'visa',
            'card_number' => '1234',
            'customer_id' => $this->contactId,
            'used_status' => 1,
            'settlement_no' => $settlementId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Link card payment details
        $scA = DB::table('settlement_card_payments')->insertGetId([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementId,
            'amount' => 500.00,
            'card_type' => 'visa',
            'card_number' => '1234',
            'daily_card_id' => $cardA,
            'customer_id' => $this->contactId,
            'pump_payment_id' => $popA,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update the pump operator payment parent_id link
        DB::table('pump_operator_payments')->where('id', $popA)->update(['parent_id' => $scA]);

        // Shortage for Shift A
        $popShortA = DB::table('pump_operator_payments')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'payment_type' => 'shortage',
            'payment_amount' => 50.00,
            'shift_id' => $shiftA,
            'is_used' => 1,
            'settlement_no' => $settlementId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shortA = DB::table('settlement_shortage_payments')->insertGetId([
            'business_id' => $this->businessId,
            'settlement_no' => $settlementId,
            'amount' => 50.00,
            'current_shortage' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('pump_operator_payments')->where('id', $popShortA)->update(['parent_id' => $shortA]);

        // 5. Seed unlinked payments for Shift B
        $popB = DB::table('pump_operator_payments')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'payment_type' => 'card',
            'payment_amount' => 600.00,
            'collection_form_no' => 'COL-B',
            'shift_id' => $shiftB,
            'is_used' => 0,
            'settlement_no' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cardB = DB::table('daily_cards')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'collection_no' => 'COL-B',
            'amount' => 600.00,
            'card_type' => 'visa',
            'card_number' => '5678',
            'customer_id' => $this->contactId,
            'used_status' => 0,
            'settlement_no' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6. Request to update the settlement shift to Shift B
        $user = \App\User::findOrFail($this->userId);
        $this->actingAs($user);

        $request = Request::create("/petro/settlement-pd/{$settlementId}", 'PUT', [
            'work_shift' => [$shiftB],
            'transaction_date' => now()->toDateString(),
            'pump_operator_id' => $this->pumpOperatorId,
            'location_id' => 1,
            'shift_number' => $shiftB,
            'source' => 'petro_pd',
        ]);
        $request->setLaravelSession(session());
        app()->instance('request', $request);

        $response = app(SettlementPDController::class)->update($request, $settlementId);
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['success']);

        // 7. Verify Shift A payments are unlinked
        $this->assertEquals(0, DB::table('daily_cards')->where('id', $cardA)->value('used_status'));
        $this->assertNull(DB::table('daily_cards')->where('id', $cardA)->value('settlement_no'));
        
        $this->assertEquals(0, DB::table('pump_operator_payments')->where('id', $popA)->value('is_used'));
        $this->assertNull(DB::table('pump_operator_payments')->where('id', $popA)->value('settlement_no'));
        $this->assertNull(DB::table('pump_operator_payments')->where('id', $popA)->value('parent_id'));

        $this->assertEquals(0, DB::table('pump_operator_payments')->where('id', $popShortA)->value('is_used'));
        $this->assertNull(DB::table('pump_operator_payments')->where('id', $popShortA)->value('settlement_no'));
        
        $this->assertFalse(DB::table('settlement_card_payments')->where('id', $scA)->exists());
        $this->assertFalse(DB::table('settlement_shortage_payments')->where('id', $shortA)->exists());

        // 8. Verify Shift B payments are linked
        $this->assertEquals(1, DB::table('daily_cards')->where('id', $cardB)->value('used_status'));
        $this->assertEquals($settlementId, DB::table('daily_cards')->where('id', $cardB)->value('settlement_no'));

        $this->assertEquals(1, DB::table('pump_operator_payments')->where('id', $popB)->value('is_used'));
        $this->assertEquals($settlementId, DB::table('pump_operator_payments')->where('id', $popB)->value('settlement_no'));

        $this->assertTrue(DB::table('settlement_card_payments')->where('daily_card_id', $cardB)->exists());
        $newParentId = DB::table('settlement_card_payments')->where('daily_card_id', $cardB)->value('id');
        $this->assertEquals($newParentId, DB::table('pump_operator_payments')->where('id', $popB)->value('parent_id'));

        // Verify PumpOperatorAssignment has been updated
        $this->assertNull(DB::table('pump_operator_assignments')->where('shift_id', $shiftA)->value('settlement_id'));
        $this->assertEquals(0, DB::table('pump_operator_assignments')->where('shift_id', $shiftA)->value('closed_in_settlement'));

        $this->assertEquals($settlementId, DB::table('pump_operator_assignments')->where('shift_id', $shiftB)->value('settlement_id'));
        $this->assertEquals(1, DB::table('pump_operator_assignments')->where('shift_id', $shiftB)->value('closed_in_settlement'));
    }
}
