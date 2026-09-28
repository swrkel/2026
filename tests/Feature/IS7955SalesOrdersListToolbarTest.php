<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrdersListToolbarTest extends TestCase
{
    public function test_sales_orders_list_uses_datatable_with_standard_toolbar_buttons(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($indexView);
        $this->assertStringContainsString('id="sales_orders_table"', $indexView);
        $this->assertStringContainsString("$('#sales_orders_table').DataTable(", $indexView);
        $this->assertStringContainsString('buttons:', $indexView);
        $this->assertStringContainsString("'csv'", $indexView);
        $this->assertStringContainsString("'excel'", $indexView);
        $this->assertStringContainsString("'pdf'", $indexView);
        $this->assertStringContainsString("'print'", $indexView);
        $this->assertStringContainsString("'colvis'", $indexView);
        $this->assertStringContainsString('btn-default', $indexView);
        $this->assertStringContainsString('serverSide: true', $indexView);
        $this->assertStringContainsString('ajax:', $indexView);
    }
}



