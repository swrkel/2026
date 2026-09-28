<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrderDeliveryDateAndLocationFormFieldsTest extends TestCase
{
    public function test_delivery_date_and_location_fields_present_in_templates(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');
        $editView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/edit.blade.php');

        $this->assertIsString($indexView);
        $this->assertIsString($editView);

        // Check Delivery Date input in Create tab
        $this->assertStringContainsString('name="delivery_date"', $indexView);
        $this->assertStringContainsString('Delivery Date:', $indexView);

        // Check Delivery Date input in Edit view
        $this->assertStringContainsString('name="delivery_date"', $editView);
        $this->assertStringContainsString('Delivery Date:', $editView);

        // Check Location labels
        $this->assertStringContainsString('Location:', $indexView);
        $this->assertStringContainsString('Location:', $editView);
    }
}
