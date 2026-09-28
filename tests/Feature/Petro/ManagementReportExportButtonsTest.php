<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class ManagementReportExportButtonsTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function management_report_page_contains_horizontal_button_group_with_purple_export_buttons(): void
    {
        $user = User::find($this->userId);
        
        $business = DB::table('business')->where('id', $this->businessId)->first();
        
        $this->withSession([
            'business.id' => $this->businessId,
            'business.currency_id' => $business->currency_id ?? 1,
            'user.business_id' => $this->businessId,
            'user.id' => $this->userId,
        ]);
        
        $this->actingAs($user);

        $response = $this->get('/reports/management');

        $response->assertStatus(200);

        // Verify that the export buttons are grouped inside a button group
        $response->assertSee('class="btn-group"', false);
        
        // Verify that the export buttons have btn-purple class
        $response->assertSee('class="btn btn-purple"', false);
        $response->assertSee('Export to CSV', false);
        $response->assertSee('Export to Excel', false);
        $response->assertSee('Export to PDF', false);
    }

    /** @test */
    public function daily_report_export_page_renders_successfully(): void
    {
        $user = User::find($this->userId);
        
        $business = DB::table('business')->where('id', $this->businessId)->first();
        
        $this->withSession([
            'business.id' => $this->businessId,
            'business.currency_id' => $business->currency_id ?? 1,
            'user.business_id' => $this->businessId,
            'user.id' => $this->userId,
        ]);
        
        $this->actingAs($user);

        $response = $this->get('/reports/daily-report?print_only=true&action_r=export_excel');

        $response->assertStatus(200);
    }
}
