<?php

namespace Tests\Feature;

use App\BusinessLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LocationSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_default_interval_when_none_set()
    {
        // Arrange: create a business location without interval
        $businessId = 1001;
        $location = BusinessLocation::create([
            'business_id' => $businessId,
            'name' => 'Test Location',
            'is_active' => 1,
        ]);

        // Act: call the ajax endpoint without interval set
        $response = $this
            ->withSession(['user.business_id' => $businessId])
            ->get('/business-location/settings/ajax?location_id=' . $location->id);

        // Assert
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('auto_synchronization_intervals', $json);
        $this->assertEquals(5, (int)$json['auto_synchronization_intervals']);
    }

    public function test_returns_saved_interval_when_set()
    {
        // Arrange: create a business location with interval
        $businessId = 1002;
        $location = BusinessLocation::create([
            'business_id' => $businessId,
            'name' => 'Test Location 2',
            'is_active' => 1,
            'auto_synchronization_intervals' => 15,
        ]);

        // Act
        $response = $this
            ->withSession(['user.business_id' => $businessId])
            ->get('/business-location/settings/ajax?location_id=' . $location->id);

        // Assert
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('auto_synchronization_intervals', $json);
        $this->assertEquals(15, (int)$json['auto_synchronization_intervals']);
    }

    public function test_returns_default_interval_without_location_id()
    {
        // Arrange: create a business location without interval
        $businessId = 1003;
        BusinessLocation::create([
            'business_id' => $businessId,
            'name' => 'Default Location',
            'is_active' => 1,
        ]);

        // Act: call the ajax endpoint without location_id
        $response = $this
            ->withSession(['user.business_id' => $businessId])
            ->get('/business-location/settings/ajax');

        // Assert
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('auto_synchronization_intervals', $json);
        $this->assertEquals(5, (int)$json['auto_synchronization_intervals']);
    }
}
