<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrdersSidebarMenuTest extends TestCase
{
    public function test_sales_orders_is_a_single_sidebar_entry_above_distribution_invoices(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $legacySidebar = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/layouts/partials/sidebar.blade.php');
        $v2Sidebar = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/layouts_v2/partials/sidebar.blade.php');

        $this->assertIsString($legacySidebar);
        $this->assertIsString($v2Sidebar);

        foreach ([$legacySidebar, $v2Sidebar] as $sidebar) {
            $salesOrdersPos = strpos($sidebar, "route('distribution.sales_orders.index')");
            $invoicesPos = strpos($sidebar, "route('distribution.invoices.index')");

            $this->assertNotFalse($salesOrdersPos, 'Sales Orders sidebar link is missing.');
            $this->assertNotFalse($invoicesPos, 'Distribution invoices sidebar link is missing.');
            $this->assertLessThan($invoicesPos, $salesOrdersPos, 'Sales Orders should appear above Distribution invoices.');
            $this->assertStringNotContainsString('Create Sales Orders', $sidebar, 'Create Sales Orders should not be a separate sidebar item.');
        }
    }
}
