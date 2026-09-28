<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PetroSettlementEditPermissionTest extends TestCase
{
    use RefreshDatabase;
    use MockeryPHPUnitIntegration;

    public function test_permitted_user_sees_meter_sale_controls()
    {
        Permission::firstOrCreate(['name' => 'settlement.edit', 'guard_name' => 'web']);

        $user = User::create([
            'first_name' => 'Permitted',
            'username' => 'perm_user',
            'password' => Hash::make('secret'),
        ]);
        $user->givePermissionTo('settlement.edit');

        $this->actingAs($user);

        $view = view('petro::settlement.partials.meter_sale_form', [
            'pump_nos' => [1 => 'Pump 1'],
            'discount_types' => ['fixed' => 'Fixed'],
            'meter_sale' => [],
        ])->render();

        $this->assertStringContainsString('btn_meter_sale', $view);
    }

    public function test_permitted_user_sees_other_sale_controls()
    {
        Permission::firstOrCreate(['name' => 'settlement.edit', 'guard_name' => 'web']);

        $user = User::create([
            'first_name' => 'Permitted',
            'username' => 'perm_other_user',
            'password' => Hash::make('secret'),
        ]);
        $user->givePermissionTo('settlement.edit');

        session()->put('user.business_id', 1);
        session()->put('business.default_store', 1);
        session()->put('business.id', 1);

        $businessMock = Mockery::mock('alias:App\Business');
        $businessQuery = Mockery::mock();
        $businessQuery->shouldReceive('first')->andReturn((object) ['currency_precision' => 2]);
        $businessMock->shouldReceive('where')->with('id', 1)->andReturn($businessQuery);

        $this->actingAs($user);

        $view = view('petro::settlement.partials.other_sale', [
            'stores' => [1 => 'Main store'],
            'bulk_tanks' => [],
            'items' => [1 => 'Fuel'],
            'combinedOtherSales' => [],
            'pump_other_sale_final_total' => 0,
            'currency_precision' => 2,
        ])->render();

        $this->assertStringContainsString('btn_other_sale', $view);
    }

    public function test_non_permitted_user_cannot_save_meter_sale()
    {
        $user = User::create([
            'first_name' => 'Denied',
            'username' => 'denied_user',
            'password' => Hash::make('secret'),
        ]);

        $this->actingAs($user);

        $response = $this
            ->withSession(['business.id' => 1])
            ->post('/petro/settlement/save-meter-sale', [
                'pump_id' => 1,
            ]);

        $response->assertStatus(403);
    }

    public function test_non_permitted_user_cannot_save_other_sale()
    {
        $user = User::create([
            'first_name' => 'Denied',
            'username' => 'denied_user2',
            'password' => Hash::make('secret'),
        ]);

        $this->actingAs($user);

        $response = $this
            ->withSession(['business.id' => 1])
            ->post('/petro/settlement/save-other-sale', [
                'store_id' => 1,
                'product_id' => 1,
                'price' => 100,
                'qty' => 1,
                'balance_stock' => 10,
                'discount' => 0,
                'discount_type' => 'fixed',
                'discount_amount' => 0,
                'sub_total' => 100,
            ]);

        $response->assertStatus(403);
    }
}
