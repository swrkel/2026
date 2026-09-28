<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrderQuickAddCustomerTest extends TestCase
{
    public function test_quick_add_customer_updates_sales_order_customer_dropdown_without_page_reload(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $salesOrderCreate = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/create.blade.php');
        $contactCreate = file_get_contents($projectRoot . '/resources/views/contact/create.blade.php');

        $this->assertIsString($salesOrderCreate);
        $this->assertIsString($contactCreate);

        $this->assertStringContainsString("new Option(result.data.name, result.data.id, true, true)", $salesOrderCreate);
        $this->assertStringContainsString("$('#customer_id').append(option).trigger('change');", $salesOrderCreate);
        $this->assertStringNotContainsString('location.reload();', $contactCreate);
    }
}
