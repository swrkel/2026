<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\Settlement;

class PetroPdRefreshReusesActiveSettlementTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function pd_settlement_refresh_reuses_the_unfinished_active_settlement(): void
    {
        $settlementNo = 'PDST-REFRESH-' . uniqid();
        $settlement = $this->seedActivePdSettlement($settlementNo);

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->get('/petropd/pd-settlement');

        $response->assertOk();
        $response->assertSee('value="' . e($settlement->settlement_no) . '"', false);
    }

    private function seedActivePdSettlement(string $settlementNo): Settlement
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        $id = DB::table('settlements')->insertGetId([
            'settlement_no' => $settlementNo,
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([]),
            'note' => null,
            'total_amount' => 0,
            'status' => 1,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Settlement::findOrFail($id);
    }
}
