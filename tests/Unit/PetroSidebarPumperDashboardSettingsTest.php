<?php

namespace Tests\Unit;

use Tests\TestCase;

class PetroSidebarPumperDashboardSettingsTest extends TestCase
{
    /** @test */
    public function pump_dashboard_settings_sidebar_item_requires_company_setting_permission(): void
    {
        $sidebar = file_get_contents(base_path('Modules/Petro/Resources/views/layouts_v2/partials/sidebar.blade.php'));

        $this->assertStringContainsString("'pumper_dashboard_settings' => 0", $sidebar);
        $this->assertStringContainsString('@if ($pump_operator_dashboard && $pumper_dashboard_settings)', $sidebar);
        $this->assertStringContainsString("PumpOperatorController@setting_dash", $sidebar);
    }
}
