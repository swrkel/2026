<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrderUrlActionTest extends TestCase
{
    public function test_sales_orders_list_contains_sales_order_url_action(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($indexView);
        $this->assertStringContainsString('Sales Order URL', $indexView);
    }
}
