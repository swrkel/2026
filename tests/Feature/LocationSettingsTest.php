<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\BusinessLocation;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LocationSettingsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_location_settings_update_does_not_throw_exception()
    {
        $business_id = 12; // Lashini business
        $user = User::where('business_id', $business_id)->first();
        $location = BusinessLocation::where('business_id', $business_id)->first();

        if (!$user || !$location) {
            $this->markTestSkipped('User or Location not found for business ID 12.');
        }

        // Add store permission for the user to manage offline sync
        \App\UserStorePermission::updateOrCreate([
            'business_id' => $business_id,
            'user_id' => $user->id,
        ], [
            'offline_sync_manage' => 1
        ]);

        // Reset current interval to 0
        $location->auto_synchronization_intervals = 0;
        $location->save();

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $business_id,
                'business.id' => $business_id
            ])
            ->post('/business-location/' . $location->id . '/settings', [
                'print_receipt_on_invoice' => 1,
                'receipt_printer_type' => 'browser',
                'auto_synchronization_intervals' => 15
            ]);

        $response->assertSessionHas('status', [
            'success' => 1,
            'msg' => __('receipt.receipt_settings_updated')
        ]);

        $location->refresh();
        $this->assertEquals(15, $location->auto_synchronization_intervals);
    }

    public function test_location_settings_update_to_zero_becomes_one()
    {
        $business_id = 12; // Lashini business
        $user = User::where('business_id', $business_id)->first();
        $location = BusinessLocation::where('business_id', $business_id)->first();

        if (!$user || !$location) {
            $this->markTestSkipped('User or Location not found.');
        }

        \App\UserStorePermission::updateOrCreate([
            'business_id' => $business_id,
            'user_id' => $user->id,
        ], [
            'offline_sync_manage' => 1
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $business_id,
                'business.id' => $business_id
            ])
            ->post('/business-location/' . $location->id . '/settings', [
                'print_receipt_on_invoice' => 1,
                'receipt_printer_type' => 'browser',
                'auto_synchronization_intervals' => 0
            ]);

        $response->assertSessionHas('status', [
            'success' => 1,
            'msg' => __('receipt.receipt_settings_updated')
        ]);

        $location->refresh();
        $this->assertEquals(1, $location->auto_synchronization_intervals);
    }

    public function test_business_admin_can_update_settings_without_user_store_permission()
    {
        $business_id = 12; // Lashini business
        $user = User::where('business_id', $business_id)->first();
        $location = BusinessLocation::where('business_id', $business_id)->first();

        if (!$user || !$location) {
            $this->markTestSkipped('User or Location not found for business ID 12.');
        }

        // Delete any store permission for this user
        \App\UserStorePermission::where('user_id', $user->id)->delete();

        // Reset current interval to 0
        $location->auto_synchronization_intervals = 0;
        $location->save();

        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $business_id,
                'business.id' => $business_id
            ])
            ->post('/business-location/' . $location->id . '/settings', [
                'print_receipt_on_invoice' => 1,
                'receipt_printer_type' => 'browser',
                'auto_synchronization_intervals' => 15
            ]);

        $response->assertSessionHas('status', [
            'success' => 1,
            'msg' => __('receipt.receipt_settings_updated')
        ]);

        $location->refresh();
        $this->assertEquals(15, $location->auto_synchronization_intervals);
    }

    public function test_store_level_user_with_permission_can_update_settings()
    {
        $business_id = 12; // Lashini business
        $admin = User::where('business_id', $business_id)->first();
        $location = BusinessLocation::where('business_id', $business_id)->first();

        if (!$admin || !$location) {
            $this->markTestSkipped('Admin or Location not found for business ID 12.');
        }

        // Create a new store-level user using direct model creation
        $store_user = User::create([
            'business_id' => $business_id,
            'surname' => 'Mr',
            'first_name' => 'Store',
            'last_name' => 'User',
            'email' => 'storeuser@example.com',
            'username' => 'store_user_test',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'status' => 'active',
            'location_permissions' => json_encode([$location->id])
        ]);

        // Mock store and store permission
        $store = \App\Store::create([
            'business_id' => $business_id,
            'location_id' => $location->id,
            'name' => 'Test Store',
            'status' => 1,
            'is_main' => 0
        ]);

        \App\UserStorePermission::create([
            'business_id' => $business_id,
            'store_id' => $store->id,
            'user_id' => $store_user->id,
            'offline_sync_manage' => 1
        ]);

        // Reset current interval to 0
        $location->auto_synchronization_intervals = 0;
        $location->save();

        $response = $this->actingAs($store_user)
            ->withSession([
                'user.business_id' => $business_id,
                'business.id' => $business_id
            ])
            ->post('/business-location/' . $location->id . '/settings', [
                'print_receipt_on_invoice' => 1,
                'receipt_printer_type' => 'browser',
                'auto_synchronization_intervals' => 20
            ]);

        $response->assertSessionHas('status', [
            'success' => 1,
            'msg' => __('receipt.receipt_settings_updated')
        ]);

        $location->refresh();
        $this->assertEquals(20, $location->auto_synchronization_intervals);
    }
}

