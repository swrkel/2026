<?php

namespace Tests\Feature;

use App\Business;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SuperadminAllBusinessesWithNoOwnerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_all_businesses_page_loads_when_business_has_no_owner(): void
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

        // 3. Create a temporary owner user
        $tempOwnerId = DB::table('users')->insertGetId([
            'first_name' => 'Temp',
            'last_name' => 'Owner',
            'username' => 'temp_owner_' . uniqid(),
            'email' => 'temp_owner_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Create the business pointing to the temporary owner
        $businessId = DB::table('business')->insertGetId([
            'name' => 'Business With No Owner',
            'currency_id' => $currencyId,
            'owner_id' => $tempOwnerId,
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

        // 5. Delete the temporary owner by bypassing foreign key checks to simulate a orphaned owner_id
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->where('id', $tempOwnerId)->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 6. Request superadmin businesses list page
        $response = $this->actingAs($superadminUser)
            ->withSession([
                'user.business_id' => $businessId,
            ])
            ->get('/superadmin/business');

        $response->assertStatus(200);
        $response->assertSee('Business With No Owner');
    }
}
