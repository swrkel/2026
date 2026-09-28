<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class AddDipChartSaveTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function save_dip_chart_via_ajax_returns_json_success(): void
    {
        // Seed a fuel tank
        $tankId = DB::table('fuel_tanks')->insertGetId([
            'business_id' => $this->businessId,
            'fuel_tank_number' => 'T-TEST-01',
            'product_id' => $this->productId,
            'location_id' => 1,
            'storage_volume' => 5000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::where('business_id', $this->businessId)->firstOrFail();

        // Submit via AJAX
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
                'currency' => $this->currencySessionData(),
            ])
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest'
            ])
            ->post('/petro/save-dip-chart', [
                'date_time' => now()->format('Y-m-d H:i:s'),
                'tank_id' => $tankId,
                'tank_manufacturer' => 'Test Manufacturer',
                'tank_manufacturer_contact' => '123456',
                'sheet_name' => 'Sheet Test 1',
                'dip_reading' => ['10', '20'],
                'dip_reading_value' => ['100', '200'],
            ]);

        // Assert success JSON and no 302 redirect
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }
}
