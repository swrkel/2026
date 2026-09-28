<?php

namespace Tests\Feature;

use App\Business;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Superadmin\Entities\Subscription;
use Tests\TestCase;

class SuperadminManageCheckboxTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unchecking_permission_checkbox_saves_as_zero(): void
    {
        $this->withoutExceptionHandling();

        // 1. Setup Superadmin authentication gate
        $superadminUser = User::firstOrCreate(
            ['username' => 'superadmin_test'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'superadmin@test.com',
                'password' => bcrypt('password'),
            ]
        );

        Gate::define('superadmin', function () {
            return true;
        });

        // 2. Create a currency
        $currencyId = DB::table('currencies')->value('id') ?? DB::table('currencies')->insertGetId([
            'country' => 'Test',
            'currency' => 'Test Rupee',
            'code' => 'TST',
            'symbol' => 'Rs',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Create a business owner
        $ownerId = DB::table('users')->insertGetId([
            'first_name' => 'Owner',
            'last_name' => 'User',
            'username' => 'business_owner_' . uniqid(),
            'email' => 'owner_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Create the business
        $businessId = DB::table('business')->insertGetId([
            'name' => 'Manage Checkbox Test Business',
            'currency_id' => $currencyId,
            'owner_id' => $ownerId,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Create a subscription with only_walkin = 1 in package_details using Subscription::create
        $packageDetails = [
            'only_walkin' => 1,
            'tank_dip_chart' => 1,
            'edit_settlement_no_change' => 1,
            'allow_duplicate_order_numbers' => 1,
            'allowed_tanks' => 1,
            'room_subscribe' => 1,
            'room_added' => 1,
            'room_could_be_added' => 1,
        ];

        $subscription = Subscription::create([
            'business_id' => $businessId,
            'package_id' => 1,
            'package_price' => 0,
            'package_details' => $packageDetails,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => 'approved',
            'created_id' => $superadminUser->id,
            'module_activation_details' => json_encode([]),
            'customer_credit_notification_type' => []
        ]);

        // Verify pre-condition: subscription has only_walkin = 1
        $this->assertEquals(1, $subscription->package_details['only_walkin'] ?? null);
        $this->assertEquals(1, $subscription->package_details['tank_dip_chart'] ?? null);
        $this->assertEquals(1, $subscription->package_details['edit_settlement_no_change'] ?? null);
        $this->assertEquals(1, $subscription->package_details['allow_duplicate_order_numbers'] ?? null);

        // 6. Request to save-manage but WITHOUT 'only_walkin' in the request (simulating unchecked checkbox)
        $response = $this->actingAs($superadminUser)
            ->withSession([
                'user.business_id' => $businessId,
            ])
            ->post('/superadmin/business/save-manage/' . $businessId, [
                'currency_id' => $currencyId,
                'backup_sms_numbers' => '',
                'notify_backup_restore_sms' => 'No',
            ]);

        // 7. Assert it redirects/succeeds
        $response->assertStatus(302);
        $response->assertSessionHas('status', ['success' => 1, 'msg' => 'Success']);

        // 8. Refresh and assert only_walkin is now 0
        $subscription->refresh();
        $this->assertEquals(0, $subscription->package_details['only_walkin'] ?? null);
        $this->assertEquals(0, $subscription->package_details['tank_dip_chart'] ?? null);
        $this->assertEquals(0, $subscription->package_details['edit_settlement_no_change'] ?? null);
        $this->assertEquals(0, $subscription->package_details['allow_duplicate_order_numbers'] ?? null);
    }

    public function test_superadmin_permission_check_respects_subscription(): void
    {
        $superadminUser = User::firstOrCreate(
            ['username' => 'superadmin_test'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'superadmin@test.com',
                'password' => bcrypt('password'),
            ]
        );

        Gate::define('superadmin', function () {
            return true;
        });

        $currencyId = DB::table('currencies')->value('id') ?? DB::table('currencies')->insertGetId([
            'country' => 'Test',
            'currency' => 'Test Rupee',
            'code' => 'TST',
            'symbol' => 'Rs',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ownerId = DB::table('users')->insertGetId([
            'first_name' => 'Owner',
            'last_name' => 'User',
            'username' => 'business_owner_' . uniqid(),
            'email' => 'owner_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $businessId = DB::table('business')->insertGetId([
            'name' => 'Permission Check Test Business',
            'currency_id' => $currencyId,
            'owner_id' => $ownerId,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subscription = Subscription::create([
            'business_id' => $businessId,
            'package_id' => 1,
            'package_price' => 0,
            'package_details' => [
                'tank_dip_chart' => 0,
            ],
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => 'approved',
            'created_id' => $superadminUser->id,
            'module_activation_details' => json_encode([]),
            'customer_credit_notification_type' => []
        ]);

        $this->actingAs($superadminUser);

        $hasPermission = \App\Utils\ModuleUtil::hasThePermissionInSubscription($businessId, 'tank_dip_chart');
        $this->assertFalse($hasPermission);
    }

    public function test_save_manage_saves_checkboxes_for_waiting_subscription(): void
    {
        $this->withoutExceptionHandling();

        $superadminUser = User::firstOrCreate(
            ['username' => 'superadmin_test'],
            [
                'first_name' => 'Super',
                'last_name'  => 'Admin',
                'email'      => 'superadmin@test.com',
                'password'   => bcrypt('password'),
            ]
        );

        Gate::define('superadmin', function () {
            return true;
        });

        $currencyId = DB::table('currencies')->value('id') ?? DB::table('currencies')->insertGetId([
            'country'            => 'Test',
            'currency'           => 'Test Rupee',
            'code'               => 'TST',
            'symbol'             => 'Rs',
            'thousand_separator' => ',',
            'decimal_separator'  => '.',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $ownerId = DB::table('users')->insertGetId([
            'first_name' => 'Owner',
            'last_name'  => 'User',
            'username'   => 'business_owner_' . uniqid(),
            'email'      => 'owner_' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'language'   => 'en',
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $businessId = DB::table('business')->insertGetId([
            'name'                 => 'Waiting Sub Checkbox Test',
            'currency_id'          => $currencyId,
            'owner_id'             => $ownerId,
            'stop_selling_before'  => 0,
            'auto_repair_settings' => '',
            'asset_settings'       => '',
            'font_size'            => 12,
            'font_family'          => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision'   => '2',
            'quantity_precision'   => '2',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // Subscription with status 'waiting' (not 'approved')
        $subscription = Subscription::create([
            'business_id'                       => $businessId,
            'package_id'                        => 1,
            'package_price'                     => 0,
            'package_details'                   => ['tank_dip_chart' => 0, 'enable_petro_module' => 0],
            'start_date'                        => now()->subDays(5)->toDateString(),
            'end_date'                          => now()->addDays(30)->toDateString(),
            'status'                            => 'waiting',
            'created_id'                        => $superadminUser->id,
            'module_activation_details'         => json_encode([]),
            'customer_credit_notification_type' => [],
        ]);

        // Verify pre-condition: tank_dip_chart is 0
        $this->assertEquals(0, $subscription->package_details['tank_dip_chart'] ?? 0);

        // POST save-manage WITH tank_dip_chart checked
        $response = $this->actingAs($superadminUser)
            ->withSession(['user.business_id' => $businessId])
            ->post('/superadmin/business/save-manage/' . $businessId, [
                'currency_id'               => $currencyId,
                'backup_sms_numbers'        => '',
                'notify_backup_restore_sms' => 'No',
                'tank_dip_chart'            => 1,
                'enable_petro_module'       => 1,
            ]);

        $response->assertStatus(302);

        // Refresh and assert tank_dip_chart is now 1 (saved despite 'waiting' status)
        $subscription->refresh();
        $this->assertEquals(1, $subscription->package_details['tank_dip_chart'] ?? 0,
            'tank_dip_chart should be saved to waiting subscription');
        $this->assertEquals(1, $subscription->package_details['enable_petro_module'] ?? 0,
            'enable_petro_module should be saved to waiting subscription');
    }

    public function test_save_manage_does_not_crash_when_no_active_subscription(): void
    {
        $this->withoutExceptionHandling();

        $superadminUser = User::firstOrCreate(
            ['username' => 'superadmin_test'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'superadmin@test.com',
                'password' => bcrypt('password'),
            ]
        );

        Gate::define('superadmin', function () {
            return true;
        });

        $currencyId = DB::table('currencies')->value('id') ?? DB::table('currencies')->insertGetId([
            'country' => 'Test',
            'currency' => 'Test Rupee',
            'code' => 'TST',
            'symbol' => 'Rs',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ownerId = DB::table('users')->insertGetId([
            'first_name' => 'Owner',
            'last_name' => 'User',
            'username' => 'business_owner_' . uniqid(),
            'email' => 'owner_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $businessId = DB::table('business')->insertGetId([
            'name' => 'No Sub Test Business',
            'currency_id' => $currencyId,
            'owner_id' => $ownerId,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($superadminUser)
            ->withSession([
                'user.business_id' => $businessId,
            ])
            ->post('/superadmin/business/save-manage/' . $businessId, [
                'currency_id' => $currencyId,
                'backup_sms_numbers' => '',
                'notify_backup_restore_sms' => 'No',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('status', ['success' => 1, 'msg' => 'Success']);
    }

    public function test_dip_management_tabs_visible_for_approved_subscription(): void
    {
        $this->withoutExceptionHandling();

        $currencyId = DB::table('currencies')->value('id') ?? DB::table('currencies')->insertGetId([
            'country' => 'Test',
            'currency' => 'Test Rupee',
            'code' => 'TST',
            'symbol' => 'Rs',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ownerId = DB::table('users')->insertGetId([
            'first_name' => 'Owner',
            'last_name' => 'User',
            'username' => 'business_owner_' . uniqid(),
            'email' => 'owner_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $owner = User::find($ownerId);

        $businessId = DB::table('business')->insertGetId([
            'name' => 'Approved Sub Petro Test',
            'currency_id' => $currencyId,
            'owner_id' => $ownerId,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Subscription::create([
            'business_id' => $businessId,
            'package_id' => 1,
            'package_price' => 0,
            'package_details' => [
                'tank_dip_chart' => 1,
                'dip_resetting' => 1,
            ],
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => 'approved',
            'created_id' => $ownerId,
            'module_activation_details' => json_encode([]),
            'customer_credit_notification_type' => []
        ]);

        // Access the dip-management page
        $response = $this->actingAs($owner)
            ->withSession([
                'user.business_id' => $businessId,
            ])
            ->get('/petro/dip-management');

        $response->assertStatus(200);
        $response->assertSee('#tank_dip_chart');
        $response->assertSee('#dip_resetting');
    }
}


