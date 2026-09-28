<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS1286DistributionPrefixLabelTest extends TestCase
{
    public function test_prefix_datatable_uses_sales_orders_label(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $path = $projectRoot . '/Modules/Distribution/Http/Controllers/DistributionNumberingPrefixController.php';
        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $this->assertStringContainsString("'sales_order'         => 'Sales Orders'", $contents);
    }
}

