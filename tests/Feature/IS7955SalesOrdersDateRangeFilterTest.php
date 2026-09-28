<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrdersDateRangeFilterTest extends TestCase
{
    public function test_sales_orders_list_uses_single_date_range_filter_instead_of_from_and_to_fields(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($indexView);
        $this->assertStringContainsString("Form::label('date_range'", $indexView);
        $this->assertStringContainsString("Form::text('date_range'", $indexView);
        $this->assertStringContainsString("id=\"start_date\"", $indexView);
        $this->assertStringContainsString("id=\"end_date\"", $indexView);
        $this->assertStringNotContainsString('<label>From</label>', $indexView);
        $this->assertStringNotContainsString('<label>To</label>', $indexView);
    }
}
