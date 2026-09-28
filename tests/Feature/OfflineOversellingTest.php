<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Business;
use App\User;
use App\UserStorePermission;
use Modules\Superadmin\Entities\Subscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OfflineOversellingTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\Gate::define('superadmin', function () {
            return false;
        });
        parent::tearDown();
    }

    public function test_can_offline_access_is_false_when_offline_mode_disabled_in_subscription()
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();

        // 1. Grant user store offline access
        UserStorePermission::updateOrCreate([
            'business_id' => $business->id,
            'user_id' => $user->id,
        ], [
            'offline_access' => 1
        ]);

        // 2. Set subscription offline_mode to 0
        Subscription::where('business_id', $business->id)->forceDelete();
        Subscription::create([
            'business_id' => $business->id,
            'package_id' => 1,
            'package_price' => 0,
            'package_details' => [
                'offline_mode' => 0
            ],
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(30),
            'status' => 'approved',
            'created_id' => $user->id,
            'module_activation_details' => json_encode([]),
            'customer_credit_notification_type' => []
        ]);

        // 3. Request /business/settings to trigger AppServiceProvider View composer
        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $business->id,
                'business.id' => $business->id
            ])
            ->get('/business/settings');

        $response->assertStatus(200);

        // Since offline_mode is disabled in the active subscription, can_offline_access must be false
        $shared_data = $response->original->getData();
        $this->assertArrayHasKey('can_offline_access', $shared_data);
        $this->assertFalse($shared_data['can_offline_access']);
    }

    public function test_can_offline_access_is_true_when_offline_mode_enabled_in_subscription()
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();

        // 1. Grant user store offline access
        UserStorePermission::updateOrCreate([
            'business_id' => $business->id,
            'user_id' => $user->id,
        ], [
            'offline_access' => 1
        ]);

        // 2. Set subscription offline_mode to 1
        Subscription::where('business_id', $business->id)->forceDelete();
        Subscription::create([
            'business_id' => $business->id,
            'package_id' => 1,
            'package_price' => 0,
            'package_details' => [
                'offline_mode' => 1
            ],
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(30),
            'status' => 'approved',
            'created_id' => $user->id,
            'module_activation_details' => json_encode([]),
            'customer_credit_notification_type' => []
        ]);

        // 3. Request /business/settings to trigger AppServiceProvider View composer
        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $business->id,
                'business.id' => $business->id
            ])
            ->get('/business/settings');

        $response->assertStatus(200);

        // Since offline_mode is enabled in the active subscription and user has permission, can_offline_access must be true
        $shared_data = $response->original->getData();
        $this->assertArrayHasKey('can_offline_access', $shared_data);
        $this->assertTrue($shared_data['can_offline_access']);
    }

    public function test_package_offline_mode_persists_and_propagates_to_active_subscription()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        \Illuminate\Support\Facades\Gate::define('superadmin', function () {
            return true;
        });

        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();

        // 1. Create a Package
        $package_data = \Modules\Superadmin\Entities\Package::first()->toArray();
        unset($package_data['id']);
        $package_data['name'] = 'Offline Test Package';
        $package_data['package_permissions'] = json_encode(['offline_mode' => 0]);
        $package = \Modules\Superadmin\Entities\Package::create($package_data);

        // 2. Create subscription for this package
        Subscription::where('business_id', $business->id)->forceDelete();
        $subscription = Subscription::create([
            'business_id' => $business->id,
            'package_id' => $package->id,
            'package_price' => 10,
            'package_details' => [
                'offline_mode' => 0
            ],
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(30),
            'status' => 'approved',
            'created_id' => $user->id,
            'module_activation_details' => json_encode([]),
            'customer_credit_notification_type' => []
        ]);

        // 3. Send POST request to update package and subscriptions
        $request_data = array_merge($package_data, [
            '_method' => 'PUT',
            'name' => 'Offline Test Package Updated',
            'offline_mode' => 1,
            'update_subscriptions' => 1
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'user.id' => $user->id,
                'user.business_id' => $business->id,
                'business.id' => $business->id
            ])
            ->post('/superadmin/packages/' . $package->id, $request_data);

        $response->assertStatus(302); // Redirect back to index

        // 4. Assert package has offline_mode enabled
        $package->refresh();
        $package_permissions = json_decode($package->package_permissions, true);
        $this->assertArrayHasKey('offline_mode', $package_permissions);
        $this->assertEquals(1, $package_permissions['offline_mode']);

        // 5. Assert subscription has updated package_details with offline_mode enabled
        $subscription->refresh();
        $this->assertArrayHasKey('offline_mode', $subscription->package_details);
        $this->assertEquals(1, $subscription->package_details['offline_mode']);
    }

    public function test_package_creation_saves_offline_mode()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        \Illuminate\Support\Facades\Gate::define('superadmin', function () {
            return true;
        });

        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();

        // 1. Prepare creation data
        $package_data = \Modules\Superadmin\Entities\Package::first()->toArray();
        unset($package_data['id']);
        $package_data['name'] = 'Offline Creation Test Package';
        $package_data['offline_mode'] = 1;

        // 2. Send POST request to store package
        $response = $this->actingAs($user)
            ->withSession([
                'user.id' => $user->id,
                'user.business_id' => $business->id,
                'business.id' => $business->id
            ])
            ->post('/superadmin/packages', $package_data);

        $response->assertStatus(302); // Redirects back

        // 3. Find created package
        $created_package = \Modules\Superadmin\Entities\Package::where('name', 'Offline Creation Test Package')->first();
        $this->assertNotNull($created_package);

        // 4. Assert package has offline_mode enabled in package_permissions
        $package_permissions = json_decode($created_package->package_permissions, true);
        $this->assertArrayHasKey('offline_mode', $package_permissions);
        $this->assertEquals(1, $package_permissions['offline_mode']);
    }

    public function test_edit_package_view_renders_successfully()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        \Illuminate\Support\Facades\Gate::define('superadmin', function () {
            return true;
        });

        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();

        // 1. Create a package with null package_permissions to simulate the error case
        $package_data = \Modules\Superadmin\Entities\Package::first()->toArray();
        unset($package_data['id']);
        $package_data['name'] = 'Null Permissions Package';
        $package_data['package_permissions'] = null;
        $package = \Modules\Superadmin\Entities\Package::create($package_data);

        // 2. Call edit route
        $response = $this->actingAs($user)
            ->withSession([
                'user.id' => $user->id,
                'user.business_id' => $business->id,
                'business.id' => $business->id
            ])
            ->get('/superadmin/packages/' . $package->id . '/edit');

        // 3. Assert successful rendering (no undefined variable exception)
        $response->assertStatus(200);
    }
}
