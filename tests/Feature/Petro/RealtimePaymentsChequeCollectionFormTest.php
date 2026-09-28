<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class RealtimePaymentsChequeCollectionFormTest extends PetroTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (\Illuminate\Support\Facades\Schema::connection('system')->hasTable('subscriptions')) {
            DB::connection('system')->table('subscriptions')
                ->where('business_id', $this->businessId)
                ->update([
                    'package_details' => json_encode([
                        'petro_pd_module' => 1,
                        'real_time_entries' => 1,
                    ])
                ]);
        }
    }

    /** @test */
    public function submit_cheques_endpoint_returns_new_collection_form_number(): void
    {
        $pumpId = (int) DB::table('pumps')
            ->where('business_id', $this->businessId)
            ->value('id');

        if (! $pumpId) {
            $this->markTestSkipped("Missing pump for business {$this->businessId}.");
        }

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

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->post('/real-time-entries/real-time-payments', [
                'submit_cheques' => 1,
                'customer' => [$this->contactId],
                'amount' => [150.00],
                'cheque_no' => ['CHQ-TEST-123'],
                'cheque_date' => ['2026-05-25'],
                'bank' => [''],
                'shift_number' => 20,
                'pump_operator_id' => $this->pumpOperatorId,
            ], [
                'X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertArrayHasKey('collection_form_no', $response->json());
        $this->assertNotNull($response->json('collection_form_no'));
    }
}
