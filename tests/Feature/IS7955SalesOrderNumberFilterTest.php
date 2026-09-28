<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrderNumberFilterTest extends TestCase
{
    public function test_sales_orders_list_uses_dropdown_filter_for_sales_order_number(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($indexView);
        $this->assertStringContainsString("Form::select('sales_order_no'", $indexView);
        $this->assertStringNotContainsString('<input type="text" name="sales_order_no"', $indexView);
    }
}
