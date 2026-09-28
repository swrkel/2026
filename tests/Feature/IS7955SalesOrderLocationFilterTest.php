<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrderLocationFilterTest extends TestCase
{
    public function test_sales_orders_list_uses_dropdown_filter_for_location(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($indexView);
        $this->assertStringContainsString("Form::select('location'", $indexView);
        $this->assertStringNotContainsString('<input type="text" name="location"', $indexView);
    }
}
