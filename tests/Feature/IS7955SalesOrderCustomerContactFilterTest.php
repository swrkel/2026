<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrderCustomerContactFilterTest extends TestCase
{
    public function test_sales_orders_list_uses_combined_customer_name_and_contact_filter(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($indexView);
        $this->assertStringContainsString('Customer Name / Contact Number', $indexView);
        $this->assertStringContainsString("Form::select('customer_lookup'", $indexView);
        $this->assertStringNotContainsString('<label>Customer</label>', $indexView);
        $this->assertStringNotContainsString('<label>Customer Contact</label>', $indexView);
    }
}
