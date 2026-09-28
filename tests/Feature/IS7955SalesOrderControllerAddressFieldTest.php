<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrderControllerAddressFieldTest extends TestCase
{
    public function test_controller_retrieves_address_column(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $controller = file_get_contents($projectRoot . '/Modules/Distribution/Http/Controllers/DistributionSalesOrderController.php');

        $this->assertIsString($controller);
        // Verify controller contains select or get with address column
        $this->assertStringContainsString("'landmark', 'address'", $controller);
    }
}
