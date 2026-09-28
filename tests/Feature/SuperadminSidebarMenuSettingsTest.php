<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Superadmin\Entities\Subscription;
use Tests\TestCase;

class SuperadminSidebarMenuSettingsTest extends TestCase
{
    use DatabaseTransactions;

    private function setupTestBusinessAndUser(array $packageDetails, bool $isSuperadmin = false): array
    {
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

        Gate::define('superadmin', function () use ($isSuperadmin) {
            return $isSuperadmin;
        });

        // 2. Create currency
        $currencyId = DB::table('currencies')->value('id') ?? DB::table('currencies')->insertGetId([
            'country' => 'Test Country',
            'currency' => 'Test Rupee',
            'code' => 'TST',
            'symbol' => 'Rs',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Create business owner
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

        // 4. Create business
        $businessId = DB::table('business')->insertGetId([
            'name' => 'Sidebar Test Business ' . uniqid(),
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

        // Insert standard Admin role for the business
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Admin#' . $businessId,
            'guard_name' => 'web',
            'business_id' => $businessId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => 'App\User',
            'model_id' => $ownerId,
        ]);

        // Merge standard required package options
        $defaultPackageDetails = [
            'stock_adjustment' => 1,
            'enable_petro_module' => 1,
        ];
        $finalPackageDetails = array_merge($defaultPackageDetails, $packageDetails);

        // 5. Create subscription
        Subscription::create([
            'business_id' => $businessId,
            'package_id' => 1,
            'package_price' => 0,
            'final_price' => 0,
            'package_details' => $finalPackageDetails,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => 'approved',
            'created_id' => $superadminUser->id,
            'module_activation_details' => json_encode([]),
            'customer_credit_notification_type' => []
        ]);

        return [
            'owner' => $owner,
            'business_id' => $businessId,
            'superadmin' => $superadminUser,
        ];
    }

    public function test_sidebar_menus_are_hidden_when_disabled_in_subscription_for_system_users(): void
    {
        $setup = $this->setupTestBusinessAndUser([
            'pump_operator_dashboard' => 0,
            'petro_pd_module' => 0,
            'ev_charging_module' => 0,
            'petro_pd_pd_settlement' => 1,
        ], false);

        $owner = $setup['owner'];
        $businessId = $setup['business_id'];

        $response = $this->actingAs($owner)->withSession([
            'user.id' => $owner->id,
            'user.business_id' => $businessId,
        ])->get('/home');

        $html = $response->getContent();

        $this->assertStringNotContainsString('pumper-dashboard-menu', $html, 'Pumper dashboard menu should be hidden');
        $this->assertStringNotContainsString('petro-pd-menu', $html, 'Petro PD menu should be hidden');
        $this->assertStringNotContainsString('evcharging-menu', $html, 'EV Charging menu should be hidden');
    }

    public function test_sidebar_menus_are_visible_when_enabled_in_subscription_for_system_users(): void
    {
        $setup = $this->setupTestBusinessAndUser([
            'pump_operator_dashboard' => 1,
            'petro_pd_module' => 1,
            'ev_charging_module' => 1,
            'petro_pd_pd_settlement' => 1,
        ], false);

        $owner = $setup['owner'];
        $businessId = $setup['business_id'];

        $response = $this->actingAs($owner)->withSession([
            'user.id' => $owner->id,
            'user.business_id' => $businessId,
        ])->get('/home');

        $html = $response->getContent();

        $this->assertStringContainsString('pumper-dashboard-menu', $html, 'Pumper dashboard menu should be visible');
        $this->assertStringContainsString('petro-pd-menu', $html, 'Petro PD menu should be visible');
        $this->assertStringContainsString('evcharging-menu', $html, 'EV Charging menu should be visible');
    }

    public function test_sidebar_menus_are_hidden_for_superadmin_when_disabled(): void
    {
        $setup = $this->setupTestBusinessAndUser([
            'pump_operator_dashboard' => 0,
            'petro_pd_module' => 0,
            'ev_charging_module' => 0,
            'petro_pd_pd_settlement' => 0,
        ], true);

        $superadmin = $setup['superadmin'];
        $businessId = $setup['business_id'];

        $response = $this->actingAs($superadmin)->withSession([
            'user.id' => $superadmin->id,
            'user.business_id' => $businessId,
        ])->get('/home');

        $html = $response->getContent();

        $this->assertStringNotContainsString('petro-pd-menu', $html, 'Petro PD menu should be hidden for superadmin when disabled');
        $this->assertStringNotContainsString('evcharging-menu', $html, 'EV Charging menu should be hidden for superadmin when disabled');
    }

    public function test_save_manage_saves_ev_charging_module_checkbox(): void
    {
        $setup = $this->setupTestBusinessAndUser([
            'ev_charging_module' => 0,
        ], true);

        $superadmin = $setup['superadmin'];
        $businessId = $setup['business_id'];

        $response = $this->actingAs($superadmin)
            ->withSession([
                'user.id' => $superadmin->id,
                'user.business_id' => $businessId,
            ])
            ->post('/superadmin/business/save-manage/' . $businessId, [
                'currency_id' => DB::table('currencies')->value('id'),
                'backup_sms_numbers' => '',
                'notify_backup_restore_sms' => 'No',
                'ev_charging_module' => 1,
                'ev_charging_interval' => 'Years',
                'ev_charging_length' => 1,
                'ev_charging_activated_on' => now()->toDateString(),
            ]);

        $response->assertStatus(302);

        $subscription = Subscription::where('business_id', $businessId)->orderByDesc('id')->first();
        $this->assertEquals(1, $subscription->package_details['ev_charging_module'] ?? 0);
    }
}
