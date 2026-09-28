<?php

namespace Tests\Unit;

use Tests\TestCase;

class PumperDashboardOnlyPumperAuthorizationTest extends TestCase
{
    /** @test */
    public function dashboard_tiles_and_only_pumper_pages_use_the_same_page_permissions(): void
    {
        $dashboard = file_get_contents(base_path('Modules/PumperDashboard/Resources/views/dashboard.blade.php'));
        $dayEntries = file_get_contents(base_path('Modules/PumperDashboard/Http/Controllers/PumperDayEntryController.php'));
        $payments = file_get_contents(base_path('Modules/PumperDashboard/Http/Controllers/PumpOperatorPaymentController.php'));
        $closingShift = file_get_contents(base_path('Modules/PumperDashboard/Http/Controllers/ClosingShiftController.php'));
        $actions = file_get_contents(base_path('Modules/PumperDashboard/Http/Controllers/PumpOperatorActionsController.php'));

        $this->assertStringContainsString("\$canPumperDashboard('pumper_dashboard.day_entries')", $dashboard);
        $this->assertStringContainsString("\$canPumperDashboard('pumper_dashboard.close_shift')", $dashboard);
        $this->assertStringContainsString("\$canPumperDashboard('pumper_dashboard.payment_summary')", $dashboard);
        $this->assertStringContainsString("\$canPumperDashboard('pumper_dashboard.close_pump')", $dashboard);

        $this->assertStringContainsString("authorizePumperDashboardPermission('pumper_dashboard.day_entries')", $dayEntries);
        $this->assertStringContainsString("authorizePumperDashboardPermission('pumper_dashboard.payment_summary')", $payments);
        $this->assertStringContainsString("authorizePumperDashboardPermission('pumper_dashboard.close_shift')", $closingShift);
        $this->assertStringContainsString("authorizePumperDashboardPermission('pumper_dashboard.close_pump')", $actions);
    }
}
