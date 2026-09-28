<?php

namespace Tests\Feature;

use App\Business;
use App\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RolePermissionsDirectBankDepositTest extends TestCase
{
    use DatabaseTransactions;

    public function testRolePermissionsDepositsRenamedAndDirectBankDepositSectionRemoved()
    {
        $business = Business::first() ?? Business::create([
            'name' => 'Test Business',
            'currency_id' => 1,
            'owner_id' => 1,
            'stop_selling_before' => 0,
            'auto_repair_settings' => '',
            'asset_settings' => '',
            'font_size' => 12,
            'font_family' => 'Arial',
            'weighing_scale_setting' => '',
            'currency_precision' => '2',
            'quantity_precision' => '2'
        ]);

        $user = User::where('business_id', $business->id)->first() ?? User::create([
            'first_name' => 'Tester',
            'last_name' => 'User',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'business_id' => $business->id
        ]);

        Permission::firstOrCreate(['name' => 'roles.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'roles.update', 'guard_name' => 'web']);
        $user->givePermissionTo(['roles.create', 'roles.update']);

        // Create subscription to enable deposits_module
        \Modules\Superadmin\Entities\Subscription::updateOrCreate(
            ['business_id' => $business->id, 'status' => 'approved'],
            [
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+1 year')),
                'package_details' => ['deposits_module' => 1]
            ]
        );

        // Get roles/create page
        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $business->id,
                'user.id' => $user->id,
                'business' => $business,
            ])
            ->get('/roles/create');
        $response->assertStatus(200);

        // Assert 1: "Deposits" is renamed to "Direct Bank Deposit"
        $html = $response->getContent();
        $this->assertStringContainsString('<h4><label>Direct Bank Deposit</label></h4>', $html);

        // Assert 2: "Direct Bank Deposit:" permission section is removed (commented out).
        $this->assertStringNotContainsString('<h4><label>Direct Bank Deposit:</label></h4>', $html);

        // Assert 2.5: The active checkboxes submit singular deposit.* permission names
        $this->assertStringContainsString('value="deposit.access"', $html);
        $this->assertStringContainsString('value="deposit.cash_deposit"', $html);
        $this->assertStringContainsString('value="deposit.card_deposit"', $html);
        $this->assertStringContainsString('value="deposit.cheque_deposit"', $html);
        $this->assertStringContainsString('value="deposit.transfer"', $html);
        $this->assertStringContainsString('value="deposit.realize_cheque"', $html);
        $this->assertStringNotContainsString('value="deposits_module"', $html);
        $this->assertStringNotContainsString('value="deposits.cash_deposit"', $html);

        // Assert 3: Test roles/edit page
        $role = Role::create([
            'name' => 'TestRole#' . $business->id,
            'business_id' => $business->id,
            'guard_name' => 'web'
        ]);

        $responseEdit = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $business->id,
                'user.id' => $user->id,
                'business' => $business,
            ])
            ->get("/roles/{$role->id}/edit");
        $responseEdit->assertStatus(200);

        $htmlEdit = $responseEdit->getContent();
        $this->assertStringContainsString('<h4><label>Direct Bank Deposit</label></h4>', $htmlEdit);
        $this->assertStringNotContainsString('<h4><label>Direct Bank Deposit:</label></h4>', $htmlEdit);
        
        $this->assertStringContainsString('value="deposit.access"', $htmlEdit);
        $this->assertStringContainsString('value="deposit.cash_deposit"', $htmlEdit);
        $this->assertStringContainsString('value="deposit.card_deposit"', $htmlEdit);
        $this->assertStringContainsString('value="deposit.cheque_deposit"', $htmlEdit);
        $this->assertStringContainsString('value="deposit.transfer"', $htmlEdit);
        $this->assertStringContainsString('value="deposit.realize_cheque"', $htmlEdit);
        $this->assertStringNotContainsString('value="deposits_module"', $htmlEdit);
        $this->assertStringNotContainsString('value="deposits.cash_deposit"', $htmlEdit);
    }
}
