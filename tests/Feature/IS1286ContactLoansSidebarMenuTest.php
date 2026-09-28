<?php

namespace Tests\Feature;

use App\Business;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Superadmin\Entities\Subscription;
use Tests\TestCase;

class IS1286ContactLoansSidebarMenuTest extends TestCase
{
    private $createdUserIds = [];
    private $createdBusinessIds = [];

    protected function tearDown(): void
    {
        // Cleanup created data to keep database clean without transactions
        if (!empty($this->createdBusinessIds)) {
            DB::table('subscriptions')->whereIn('business_id', $this->createdBusinessIds)->delete();
            DB::table('business')->whereIn('id', $this->createdBusinessIds)->delete();
        }
        if (!empty($this->createdUserIds)) {
            DB::table('users')->whereIn('id', $this->createdUserIds)->delete();
        }
        parent::tearDown();
    }

    public function test_loan_module_permission_saves_correctly(): void
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
        if ($superadminUser->wasRecentlyCreated) {
            $this->createdUserIds[] = $superadminUser->id;
        }

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
        $this->createdUserIds[] = $ownerId;

        // 4. Create the business
        $businessId = DB::table('business')->insertGetId([
            'name' => 'Loan Module Test Business',
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
        $this->createdBusinessIds[] = $businessId;

        // 5. Create a subscription
        $packageDetails = [
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

        // 6. Request to save-manage with loan_module = 1
        $response = $this->actingAs($superadminUser)
            ->withSession([
                'user.business_id' => $businessId,
            ])
            ->post('/superadmin/business/save-manage/' . $businessId, [
                'currency_id' => $currencyId,
                'backup_sms_numbers' => '',
                'notify_backup_restore_sms' => 'No',
                'loan_module' => 1,
            ]);

        $response->assertStatus(302);

        $subscription->refresh();
        $this->assertEquals(1, $subscription->package_details['loan_module'] ?? null);

        // 7. Request to save-manage without loan_module (simulating unchecking)
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

        $subscription->refresh();
        $this->assertEquals(0, $subscription->package_details['loan_module'] ?? null);
    }
}
