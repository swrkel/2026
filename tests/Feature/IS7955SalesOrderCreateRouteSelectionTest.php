<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrderCreateRouteSelectionTest extends TestCase
{
    public function test_create_sales_order_route_dropdown_starts_empty_with_explicit_route_prompt(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $createView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/create.blade.php');

        $this->assertIsString($createView);
        $this->assertStringContainsString("Form::select('route_id', [], null", $createView);
        $this->assertStringContainsString("'placeholder' => 'Please select the Route'", $createView);
        $this->assertStringNotContainsString("Form::select('route_id', \$routes, null", $createView);
    }
}
