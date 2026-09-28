<?php

namespace Tests\Unit;

use Tests\TestCase;

class SuperadminPetroPDManageSettingsTest extends TestCase
{
    /** @test */
    public function petro_pd_manage_page_exposes_and_persists_petro_pd_settings_toggle(): void
    {
        $view = file_get_contents(base_path('Modules/Superadmin/Resources/views/business/manage.blade.php'));
        $controller = file_get_contents(base_path('Modules/Superadmin/Http/Controllers/BusinessController.php'));
        $sidebar = file_get_contents(base_path('Modules/PetroPD/Resources/views/layouts_v2/partials/sidebar.blade.php'));

        $this->assertStringContainsString("Form::checkbox('petro_pd_settings'", $view);
        $this->assertStringContainsString("'petro_pd_settings',", $controller);
        $this->assertStringContainsString("\$package_details['petro_pd_settings']", $controller);
        $this->assertStringContainsString("'petro_pd_settings'", $sidebar);
    }
}
