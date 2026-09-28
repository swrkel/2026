<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS1286PrefixModalSalesOrdersLabelTest extends TestCase
{
    public function test_prefix_create_and_edit_modals_show_sales_orders_label(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $create = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/settings/prefix/create.blade.php');
        $edit = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/settings/prefix/edit.blade.php');

        $this->assertIsString($create);
        $this->assertIsString($edit);

        $this->assertStringContainsString("'sales_order' => 'Sales Orders'", $create);
        $this->assertStringContainsString("'sales_order' => 'Sales Orders'", $edit);
    }
}

