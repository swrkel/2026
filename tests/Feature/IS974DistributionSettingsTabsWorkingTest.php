<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS974DistributionSettingsTabsWorkingTest extends TestCase
{
    public function test_distribution_settings_uses_lazy_tab_loading_to_avoid_timeouts(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $settingsIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/settings/index.blade.php');

        $this->assertIsString($settingsIndex);
        $this->assertTrue(
            str_contains($settingsIndex, 'distribution.settings.tab'),
            'Settings page should use distribution.settings.tab for lazy-loading tab content.'
        );
        $this->assertTrue(
            str_contains($settingsIndex, 'ensureTabContentLoaded'),
            'Settings page should define ensureTabContentLoaded() to lazy-load tab content.'
        );
    }

    public function test_distribution_settings_does_not_preload_heavy_datasets_in_the_main_blade_view(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $settingsIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/settings/index.blade.php');

        $this->assertIsString($settingsIndex);
        $this->assertFalse(
            str_contains($settingsIndex, 'settings_products_'),
            'Settings page should not preload the full products list in the main Blade view.'
        );
        $this->assertFalse(
            str_contains($settingsIndex, 'Category::forDropdown'),
            'Settings page should not preload category dropdown data in the main Blade view.'
        );
        $this->assertFalse(
            str_contains($settingsIndex, 'DistributionRouteUserMap::query()'),
            'Settings page should not preload user-to-routes map data in the main Blade view.'
        );
        $this->assertFalse(
            str_contains($settingsIndex, 'BusinessLocation::forDropdown'),
            'Settings page should not preload business locations in the main Blade view.'
        );
        $this->assertFalse(
            str_contains($settingsIndex, "select('id', 'name', 'unit_id')"),
            'Settings page should not fetch products with unit_id in the main Blade view.'
        );
    }

    public function test_distribution_settings_does_not_render_all_tab_partials_server_side(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $settingsIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/settings/index.blade.php');

        $this->assertIsString($settingsIndex);
        $this->assertFalse(
            str_contains($settingsIndex, "@include('distribution::settings.provinces.index')"),
            'Settings page should not render provinces tab partial server-side.'
        );
        $this->assertFalse(
            str_contains($settingsIndex, "@include('distribution::settings.districts.index')"),
            'Settings page should not render districts tab partial server-side.'
        );
        $this->assertFalse(
            str_contains($settingsIndex, "@include('distribution::settings.areas.index')"),
            'Settings page should not render areas tab partial server-side.'
        );
        $this->assertFalse(
            str_contains($settingsIndex, "@include('distribution::settings.routes.index')"),
            'Settings page should not render routes tab partial server-side.'
        );
        $this->assertFalse(
            str_contains($settingsIndex, "@include('distribution::settings.routes.user_maps.index')"),
            'Settings page should not render user_routes tab partial server-side.'
        );
        $this->assertFalse(
            str_contains($settingsIndex, "@include('distribution::settings.prefix.index')"),
            'Settings page should not render prefix tab partial server-side.'
        );
    }
}

