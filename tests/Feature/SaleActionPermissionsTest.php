<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SaleActionPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_role_update_extracts_permissions_from_payload()
    {
        $admin = User::find(7); // ishadi2, now in business 12
        if (!$admin) {
            $admin = User::first();
        }
        $businessId = $admin->business_id;

        $role = Role::create([
            'name' => 'TestRole#' . $businessId,
            'business_id' => $businessId,
            'guard_name' => 'web'
        ]);

        Permission::firstOrCreate(['name' => 'sell.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'sell.create', 'guard_name' => 'web']);

        // Mock a request where 'permissions' input is empty (due to truncation),
        // but 'permissions_payload' contains the JSON string of permissions
        $response = $this->actingAs($admin)
            ->withSession([
                'user.business_id' => $businessId,
                'business.id' => $businessId
            ])
            ->post('/roles/' . $role->id, [
                '_method' => 'PUT',
                'name' => 'TestRole',
                'permissions' => [],
                'permissions_payload' => json_encode(['sell.view', 'sell.create'])
            ]);

        $response->assertRedirect('/roles');
        $this->assertTrue($role->fresh()->hasPermissionTo('sell.view'));
        $this->assertTrue($role->fresh()->hasPermissionTo('sell.create'));
    }

    public function test_user_store_and_update_assigns_role_scoped_to_business()
    {
        $admin = User::find(7); // ishadi2, now in business 12
        if (!$admin) {
            $admin = User::first();
        }
        $businessId = $admin->business_id;

        // Create Cashier roles for business 2
        $roleBusiness2 = Role::firstOrCreate([
            'name' => 'Cashier#2',
            'business_id' => 2,
            'guard_name' => 'web'
        ]);

        // Mock current subscription package_details to pass quota checks
        \Modules\Superadmin\Entities\Subscription::updateOrCreate(
            ['business_id' => $businessId, 'status' => 'approved'],
            [
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+1 year')),
                'package_details' => ['users' => 100]
            ]
        );

        // Call the store endpoint with a role ID belonging to a different business (ID 2).
        // It should catch the ModelNotFoundException internally, rollback, and redirect with an error status.
        $controller = app(\App\Http\Controllers\ManageUserController::class);
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'designation' => 'Cashier',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'testuser' . time() . '@example.com',
            'username' => 'testuser' . time(),
            'password' => 'password',
            'role' => $roleBusiness2->id,
            'max_sales_discount_percent' => null,
            'cmmsn_percent' => 0,
            'mobile' => [
                'code' => '94',
                'number' => '712345678',
                'country_selected' => '1'
            ]
        ]);
        $request->setLaravelSession(session());
        session(['user.business_id' => $businessId]);
        auth()->login($admin);

        $response = $controller->store($request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals(url('/users'), $response->getTargetUrl());
        
        $status = session('status');
        $this->assertEquals(0, $status['success']);
        $this->assertStringContainsString('something went wrong', strtolower($status['msg']));
    }
}
