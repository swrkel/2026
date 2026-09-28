<?php

namespace Tests\Feature\Mpcs;

use Tests\TestCase;

class F25SidebarActionTest extends TestCase
{
    /** @test */
    public function main_sidebar_f25_action_matches_registered_controller_namespace(): void
    {
        $sidebar = file_get_contents(base_path('resources/views/layouts/partials/sidebar.blade.php'));

        $this->assertNotNull(
            app('router')->getRoutes()->getByAction('Modules\\MPCS\\Http\\Controllers\\F25FormController@index')
        );
        $this->assertStringNotContainsString(
            'Modules\\Mpcs\\Http\\Controllers\\F25FormController@index',
            $sidebar
        );
    }
}
