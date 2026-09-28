<?php

namespace Tests\Feature\Distribution;

use Tests\TestCase;
use App\User;
use App\SalesAgent;
use Illuminate\Support\Facades\DB;
use Modules\Distribution\Entities\DistributionRouteUserMap;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RouteUserMapStatusAndLayoutTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_correctly_tracks_and_displays_mapping_audit_details()
    {
        $user = User::first() ?: User::factory()->create();
        $business_id = $user->business_id;

        $this->actingAs($user);
        $this->withSession([
            'user.business_id' => $business_id,
            'business' => ['id' => $business_id]
        ]);

        // 1. Create a dummy sales representative and route
        $sales_rep_id = DB::table('distribution_sales_agents')->insertGetId([
            'business_id' => $business_id,
            'name' => 'John Doe Rep',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $route_id = DB::table('distribution_routes')->insertGetId([
            'business_id' => $business_id,
            'name' => 'Route Alpha',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 2. Create the initial user map
        $map = DistributionRouteUserMap::create([
            'business_id' => $business_id,
            'route_id' => $route_id,
            'sales_rep_id' => $sales_rep_id,
            'status' => 'active',
            'added_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // 3. Make request to get mapped page
        $response = $this->get(route('distribution.route_user_maps.index'));
        $response->assertStatus(200);

        // Before fix, it should fail to show the correct added_by username
        $response->assertSee($user->username);

        // 4. Toggle status to inactive
        $toggleResponse = $this->post(route('distribution.route_user_maps.toggle_status_by_sales_rep', $sales_rep_id));
        $toggleResponse->assertRedirect();

        $map->refresh();
        $this->assertEquals('inactive', $map->status);
        $this->assertEquals('active', $map->last_status_from);
        $this->assertEquals('inactive', $map->last_status_to);
        $this->assertNotNull($map->status_changed_at);

        // 5. Re-visit the mapped page to verify the exact status change text
        $response = $this->get(route('distribution.route_user_maps.index'));
        $response->assertStatus(200);

        $expectedAuditText = "Status Changed by the User {$user->username}, from Active Status to Inactive Status on";
        $response->assertSee($expectedAuditText);
    }

    /** @test */
    public function it_displays_the_location_slider_and_auto_filter_elements()
    {
        $user = User::first() ?: User::factory()->create();
        $business_id = $user->business_id;

        $this->actingAs($user);
        $this->withSession([
            'user.business_id' => $business_id,
            'business' => ['id' => $business_id]
        ]);

        $response = $this->get(route('distribution.route_user_maps.index'));
        $response->assertStatus(200);
        $response->assertDontSee('id="location_slider"', false);
        $response->assertDontSee('id="sales_rep_slider"', false);
        $response->assertSee('class="modal fade contains_select2" id="addUserRouteMapModal"', false);
        $response->assertSee('class="modal fade contains_select2" id="editUserRouteMapModal"', false);
        $response->assertSee('minimumResultsForSearch: 0', false);
        $response->assertSee('$modal.find(\'.select2\').each(function()', false);
        $response->assertSee('id="add_route_btn_add_modal"', false);
        $response->assertSee('id="add_routes_table"', false);
        $response->assertSee('id="add_route_btn_edit_modal"', false);
        $response->assertSee('id="edit_routes_table"', false);
        $response->assertSee('.select2-results__options', false);
        $response->assertSee('max-height: 45px !important', false);
    }

    /** @test */
    public function it_saves_new_routes_mapping_successfully()
    {
        $user = User::first() ?: User::factory()->create();
        $business_id = $user->business_id;

        $this->actingAs($user);
        $this->withSession([
            'user.business_id' => $business_id,
            'business' => ['id' => $business_id]
        ]);

        $sales_rep_id = DB::table('distribution_sales_agents')->insertGetId([
            'business_id' => $business_id,
            'name' => 'Jane Doe Rep',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $route_id_1 = DB::table('distribution_routes')->insertGetId([
            'business_id' => $business_id,
            'name' => 'Route Beta',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $route_id_2 = DB::table('distribution_routes')->insertGetId([
            'business_id' => $business_id,
            'name' => 'Route Gamma',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $postData = [
            'sales_rep_id' => $sales_rep_id,
            'route_ids' => [$route_id_1, $route_id_2],
        ];

        $response = $this->post(route('distribution.route_user_maps.store'), $postData);
        $response->assertRedirect();
        $response->assertSessionHas('page', 'user_routes');

        $this->assertDatabaseHas('distribution_route_user_maps', [
            'business_id' => $business_id,
            'sales_rep_id' => $sales_rep_id,
            'route_id' => $route_id_1,
        ]);
        $this->assertDatabaseHas('distribution_route_user_maps', [
            'business_id' => $business_id,
            'sales_rep_id' => $sales_rep_id,
            'route_id' => $route_id_2,
        ]);
    }
}

