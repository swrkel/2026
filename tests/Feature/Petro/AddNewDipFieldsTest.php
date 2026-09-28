<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

/**
 * Verifies that the AJAX endpoints used by add_new_dip.blade.php return
 * the expected JSON shape so that jQuery can auto-fill #current_qty and
 * #fuel_balance_dip_reading and the + button can be enabled.
 */
class AddNewDipFieldsTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function get_tank_balance_by_id_returns_current_stock_and_dip_readings(): void
    {
        $tankId = DB::table('fuel_tanks')
            ->where('business_id', $this->businessId)
            ->value('id');

        if (! $tankId) {
            $this->markTestSkipped('No fuel_tanks for this business.');
        }

        $user = User::where('business_id', $this->businessId)->firstOrFail();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id'      => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id'          => $this->userId,
                'currency'         => $this->currencySessionData(),
            ])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('/petro/get-tank-balance-by-id/' . $tankId);

        $response->assertStatus(200);

        // Both keys must exist so jQuery can set #current_qty and #dip_readings
        $json = $response->json();
        $this->assertArrayHasKey('current_stock', $json,
            'Response must contain current_stock for #current_qty auto-fill');
        $this->assertArrayHasKey('dip_readings', $json,
            'Response must contain dip_readings to populate the dip_reading select');
    }
}
