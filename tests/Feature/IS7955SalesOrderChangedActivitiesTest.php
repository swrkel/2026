<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrderChangedActivitiesTest extends TestCase
{
    public function test_action_menu_says_changed_activities_not_changed_details(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $controller = file_get_contents($projectRoot . '/Modules/Distribution/Http/Controllers/DistributionSalesOrderController.php');

        $this->assertIsString($controller);
        $this->assertStringContainsString('Changed Activities', $controller);
        $this->assertStringNotContainsString(' Changed Details</a>', $controller);
    }

    public function test_index_blade_has_activity_log_modal_placeholder(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($indexView);
        $this->assertStringContainsString('so_activity_log_modal', $indexView);
        $this->assertStringContainsString('view-so-changed-activities', $indexView);
    }

    public function test_partial_view_exists(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $partial = $projectRoot . '/Modules/Distribution/Resources/views/sales_orders/partials/activity_log_popup.blade.php';
        $this->assertFileExists($partial);
        $content = file_get_contents($partial);
        $this->assertStringContainsString('Changed Activities', $content);
    }

    public function test_route_exists_for_so_activities(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $routes = file_get_contents($projectRoot . '/Modules/Distribution/Routes/web.php');
        $this->assertIsString($routes);
        $this->assertStringContainsString('getActivityLog', $routes);
    }
}
