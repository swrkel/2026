<?php

namespace Tests\Feature\Mpcs;

use Tests\TestCase;
use App\User;
use Modules\MPCS\Entities\FormF25Setting;
use Modules\MPCS\Entities\FormF25DeliveryLocation;
use Modules\MPCS\Entities\FormF25Header;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class F25FormTest extends TestCase
{
    use DatabaseTransactions;

    private function getAuthenticatedUser()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No user found to run test.');
        }
        return $user;
    }

    public function test_store_starting_number_saves_successfully()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->post('/mpcs/F25/settings', [
                'opening_date' => '2026-06-01',
                'starting_number' => 125
            ]);

        $response->assertRedirect();
        
        $settingExists = FormF25Setting::where('business_id', $businessId)
            ->where('opening_date', '2026-06-01')
            ->where('starting_number', 125)
            ->exists();

        $this->assertTrue($settingExists);
    }

    public function test_get_form_no_uses_starting_number_on_opening_date()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        // Clean any existing headers for this business in transaction to have clean slate
        FormF25Header::where('business_id', $businessId)->delete();

        // 0. Create a form on an earlier date with form no 0100
        FormF25Header::create([
            'business_id' => $businessId,
            'location_id' => 1,
            'form_no' => '0100',
            'transaction_date' => '2026-06-01',
            'created_by' => $user->id
        ]);

        // 1. Create a starting number setting for 2026-06-02
        FormF25Setting::create([
            'business_id' => $businessId,
            'opening_date' => '2026-06-02',
            'starting_number' => 250,
            'created_by' => $user->id
        ]);

        // 2. Fetch form no for the opening date
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->getJson('/mpcs/F25/form-no?date=2026-06-02');

        $response->assertStatus(200);
        $data = $response->json();
        
        // Expected: uses the starting number 0250, NOT 0101
        $this->assertEquals('0250', $data['form_no']);

        // 3. Create a header on that date
        $header = FormF25Header::create([
            'business_id' => $businessId,
            'location_id' => 1,
            'form_no' => '0250',
            'transaction_date' => '2026-06-02',
            'created_by' => $user->id
        ]);

        // 4. Fetch form no for the SAME opening date again
        $response2 = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->getJson('/mpcs/F25/form-no?date=2026-06-02');

        $response2->assertStatus(200);
        $data2 = $response2->json();

        // Expected: auto-increments to 0251
        $this->assertEquals('0251', $data2['form_no']);
    }

    public function test_store_multiple_delivery_locations_saves_successfully()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        // Count current delivery locations to ensure we increment
        $initialCount = FormF25DeliveryLocation::where('business_id', $businessId)->count();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->post('/mpcs/F25/delivery-locations', [
                'names' => "Location A, Location B\nLocation C",
                'is_active' => 1
            ]);

        $response->assertRedirect();

        $newCount = FormF25DeliveryLocation::where('business_id', $businessId)->count();
        $this->assertEquals($initialCount + 3, $newCount);

        // Verify Location A has the correct 4-digit code generated
        $loc = FormF25DeliveryLocation::where('business_id', $businessId)
            ->where('location_name', 'Location A')
            ->first();

        $this->assertNotNull($loc);
        $this->assertEquals('active', $loc->status);
        $this->assertEquals(4, strlen($loc->location_code));
    }

    public function test_toggle_delivery_location_status_updates_correctly()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        $location = FormF25DeliveryLocation::create([
            'business_id' => $businessId,
            'location_code' => '9999',
            'location_name' => 'Toggle Loc',
            'status' => 'active',
            'created_by' => $user->id
        ]);

        // Toggle to inactive (sending name as null/empty to simulate status-only toggle from pop up)
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->post('/mpcs/F25/delivery-locations/' . $location->id, [
                'name' => '', // Empty name simulated from status modal
                'is_active' => 0
            ]);

        $location->refresh();
        $this->assertEquals('inactive', $location->status);
    }

    public function test_form_no_uses_latest_setting_when_duplicate_opening_date_exists()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        FormF25Header::where('business_id', $businessId)->delete();
        FormF25Setting::where('business_id', $businessId)->delete();

        // 1. Create first setting for 2026-06-03 with starting number 500
        FormF25Setting::create([
            'business_id' => $businessId,
            'opening_date' => '2026-06-03',
            'starting_number' => 500,
            'created_by' => $user->id
        ]);

        // 2. Create second (newer) setting for same 2026-06-03 with starting number 800
        FormF25Setting::create([
            'business_id' => $businessId,
            'opening_date' => '2026-06-03',
            'starting_number' => 800,
            'created_by' => $user->id
        ]);

        // 3. Fetch form no for the opening date
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->getJson('/mpcs/F25/form-no?date=2026-06-03');

        $response->assertStatus(200);
        $data = $response->json();
        
        // Expected: uses the latest starting number 0800, NOT 0500
        $this->assertEquals('0800', $data['form_no']);
    }

    public function test_index_auto_selects_first_location_when_multiple_locations_exist()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->get('/mpcs/F25');

        $response->assertStatus(200);
        $response->assertViewHas('business_locations');
        $response->assertViewHas('default_location_id');
    }

    public function test_index_passes_today_date_to_view()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->get('/mpcs/F25');

        $response->assertStatus(200);
        $response->assertViewHas('today', now()->format('Y-m-d'));
    }

    public function test_index_contains_footer_elements_for_goods_issued_form()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->get('/mpcs/F25');

        $response->assertStatus(200);
        $html = $response->getContent();
        
        $this->assertStringContainsString('id="f25_footer_received_date"', $html);
        $this->assertStringContainsString('id="f25_footer_received_time"', $html);
        $this->assertStringContainsString('id="f25_footer_field_21"', $html);
        $this->assertStringContainsString('id="f25_footer_field_22"', $html);
        $this->assertStringContainsString('class="f25-header-title"', $html);
        $this->assertStringContainsString('f25-signature-container', $html);
    }

    public function test_form_no_after_opening_date_returns_incremented_number_even_when_no_forms_exist()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        FormF25Header::where('business_id', $businessId)->delete();
        FormF25Setting::where('business_id', $businessId)->delete();

        // 1. Create a setting for 2026-06-02 with starting number 250
        FormF25Setting::create([
            'business_id' => $businessId,
            'opening_date' => '2026-06-02',
            'starting_number' => 250,
            'created_by' => $user->id
        ]);

        // 2. Fetch form no for a date AFTER the opening date (2026-06-03), when no forms have been created yet
        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->getJson('/mpcs/F25/form-no?date=2026-06-03');

        $response->assertStatus(200);
        $data = $response->json();

        // Expected: should NOT return 0250 (which is only for opening date) and NOT 0001 (which ignores the setting).
        // It must return 0251 (starting_number + 1)
        $this->assertEquals('0251', $data['form_no']);
    }

    public function test_list_table_is_inside_form_tab_not_settings_tab()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->get('/mpcs/F25');

        $response->assertStatus(200);
        $html = $response->getContent();

        $listTablePos = strpos($html, 'id="f25_list_table"');
        $settingsTabPos = strpos($html, 'id="f25_settings_tab"');

        $this->assertNotFalse($listTablePos, "List table not found");
        $this->assertNotFalse($settingsTabPos, "Settings tab not found");
        $this->assertTrue($listTablePos < $settingsTabPos, "List table must be inside the form tab (before settings tab)");
    }

    public function test_delivery_locations_action_button_text_is_edit()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        // Ensure at least one delivery location exists
        FormF25DeliveryLocation::firstOrCreate([
            'business_id' => $businessId,
            'location_code' => '0001',
            'location_name' => 'Test Location',
            'status' => 'active',
            'created_by' => $user->id
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->get('/mpcs/F25');

        $response->assertStatus(200);
        $html = $response->getContent();
        // We expect the button to have "Edit" instead of "Update"
        $this->assertMatchesRegularExpression('/class="btn btn-xs btn-primary f25-open-status-modal"[^>]*>\s*Edit\s*<\/button>/s', $html);
    }

    public function test_edit_delivery_location_modal_has_correct_fields()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->get('/mpcs/F25');

        $response->assertStatus(200);
        $html = $response->getContent();
        
        // Assert new fields and layouts exist
        $this->assertMatchesRegularExpression('/id="f25_status_modal".*?class="modal-dialog modal-lg"/s', $html);
        $this->assertMatchesRegularExpression('/<textarea[^>]*id="f25_edit_location_name"/s', $html);
        $this->assertStringContainsString('id="f25_edit_is_active"', $html);
    }

    public function test_pdf_download_renders_successfully()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        $header = FormF25Header::create([
            'business_id' => $businessId,
            'location_id' => 1,
            'form_no' => '0001',
            'transaction_date' => '2026-06-02',
            'created_by' => $user->id
        ]);

        DB::table('mpcs_f25_form_lines')->insert([
            'f25_form_id' => $header->id,
            'bill_no' => 'B123',
            'description' => 'Test Item',
            'pcs' => 10,
            'qty' => 10.0,
            'unit_price' => 5.0,
            'total_amount' => 50.0,
            'received_qty' => 10.0,
            'short_qty' => 0.0,
            'excess_qty' => 0.0,
            'short_amount' => 0.0,
            'excess_amount' => 0.0,
            'line_date' => '2026-06-02',
            'line_time' => '10:00:00'
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId,
                'business.date_format' => 'm/d/Y'
            ])
            ->get('/mpcs/F25/' . $header->id . '/pdf');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_show_view_renders_successfully()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        $header = FormF25Header::create([
            'business_id' => $businessId,
            'location_id' => 1,
            'form_no' => '0001',
            'transaction_date' => '2026-06-02',
            'created_by' => $user->id
        ]);

        DB::table('mpcs_f25_form_lines')->insert([
            'f25_form_id' => $header->id,
            'bill_no' => 'B123',
            'description' => 'Test Item',
            'pcs' => 10,
            'qty' => 10.0,
            'unit_price' => 5.0,
            'total_amount' => 50.0,
            'received_qty' => 10.0,
            'short_qty' => 0.0,
            'excess_qty' => 0.0,
            'short_amount' => 0.0,
            'excess_amount' => 0.0,
            'line_date' => '2026-06-02',
            'line_time' => '10:00:00'
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId,
                'business.date_format' => 'm/d/Y'
            ])
            ->get('/mpcs/F25/' . $header->id . '/view');

        $response->assertStatus(200);
    }

    public function test_select2_scrollbar_styling_is_present()
    {
        $user = $this->getAuthenticatedUser();
        $businessId = $user->business_id;

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $businessId,
                'user.business_id' => $businessId
            ])
            ->get('/mpcs/F25');

        $response->assertStatus(200);
        $html = $response->getContent();
        
        $this->assertStringContainsString('-webkit-appearance: none', $html);
    }
}

