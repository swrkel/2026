<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrdersCustomerContactColumnTest extends TestCase
{
    public function test_sales_orders_list_uses_combined_customer_name_and_contact_column(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($indexView);
        $this->assertStringContainsString('<th>Customer Name & Contact Number</th>', $indexView);
        $this->assertStringNotContainsString('<th>Customer</th>', $indexView);
        $this->assertStringNotContainsString('<th>Contact</th>', $indexView);
        $this->assertStringContainsString('{{ $so->customer_name ?: optional($so->customer)->name }}{{ !empty($so->customer_contact) ? \' / \' . $so->customer_contact : \'\' }}', $indexView);
    }
}
