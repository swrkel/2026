<?php

namespace Tests\Feature;

use App\User;
use App\Business;
use App\BusinessLocation;
use App\Transaction;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SaleModulePermissionsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sale_module_edit_delete_permissions_flow(): void
    {
        $this->withoutExceptionHandling();

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

        $user = User::create([
            'first_name' => 'Restricted',
            'last_name' => 'User',
            'username' => 'restricted_user_' . uniqid(),
            'email' => 'restricted_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'language' => 'en',
            'status' => 'active',
            'business_id' => $business->id
        ]);

        // Ensure permissions exist
        Permission::firstOrCreate(['name' => 'direct_sell.access', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'sell.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'sell.delete', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'list_drafts', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'access_all_locations', 'guard_name' => 'web']);

        // Give the restricted user ONLY direct_sell.access and list_drafts (no sell.update or sell.delete)
        $user->givePermissionTo(['direct_sell.access', 'list_drafts', 'access_all_locations']);

        $location = BusinessLocation::firstOrCreate(
            ['business_id' => $business->id],
            ['name' => 'Test Location', 'location_id' => 'TL01']
        );

        $sell = Transaction::create([
            'business_id' => $business->id,
            'location_id' => $location->id,
            'type' => 'sell',
            'status' => 'draft',
            'is_direct_sale' => 1,
            'is_quotation' => 1,
            'payment_status' => 'due',
            'transaction_date' => '2026-05-28 10:30:00',
            'total_before_tax' => 100.0,
            'final_total' => 100.0,
            'invoice_no' => 'INV-PERM-TEST-1',
            'created_by' => $user->id,
            'contact_id' => 1
        ]);

        // 1. Assert direct access to SellController@edit is denied with 403
        try {
            $this->actingAs($user)
                ->withSession([
                    'user.business_id' => $business->id,
                    'user.id' => $user->id,
                    'business' => $business,
                ])
                ->get("/sales/{$sell->id}/edit");
            $this->fail('Access to edit route was not blocked with 403.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        // 2. Assert direct access to SellPosController@update is denied with 403
        try {
            $this->actingAs($user)
                ->withSession([
                    'user.business_id' => $business->id,
                    'user.id' => $user->id,
                    'business' => $business,
                ])
                ->put("/pos/{$sell->id}", ['status' => 'draft']);
            $this->fail('Access to update route was not blocked with 403.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        // 3. Assert draft datatable doesn't contain Edit or Delete actions for this user
        $response = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $business->id,
                'user.id' => $user->id,
                'business' => $business,
            ])
            ->get('/sells/draft-dt?is_quotation=1', ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        $data = $json['data'];
        $this->assertNotEmpty($data);

        $actionHtml = $data[0]['action'] ?? '';
        $this->assertStringNotContainsString('glyphicon-edit', $actionHtml);
        $this->assertStringNotContainsString('delete-sale', $actionHtml);
    }
}
