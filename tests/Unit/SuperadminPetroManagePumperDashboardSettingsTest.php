<?php

namespace Tests\Unit;

use Tests\TestCase;

class SuperadminPetroManagePumperDashboardSettingsTest extends TestCase
{
    /** @test */
    public function petro_manage_page_exposes_and_persists_pumper_dashboard_settings_toggle(): void
    {
        $view = file_get_contents(base_path('Modules/Superadmin/Resources/views/business/manage.blade.php'));
        $controller = file_get_contents(base_path('Modules/Superadmin/Http/Controllers/BusinessController.php'));

        $this->assertStringContainsString("Form::checkbox('pumper_dashboard_settings'", $view);
        $this->assertStringContainsString("__('superadmin::lang.pumper_dashboard_settings')", $view);
        $this->assertStringContainsString("'pumper_dashboard_settings',", $controller);
        $this->assertStringContainsString("\$package_details['pumper_dashboard_settings']", $controller);
    }
}
