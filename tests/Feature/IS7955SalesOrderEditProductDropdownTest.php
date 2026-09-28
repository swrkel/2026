<?php

namespace Tests\Feature;

use Tests\TestCase;

class IS7955SalesOrderEditProductDropdownTest extends TestCase
{
    public function test_sales_order_edit_form_uses_correct_product_iteration_without_object_keys(): void
    {
        $projectRoot = base_path();
        $editView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/edit.blade.php');

        $this->assertIsString($editView);
        $this->assertStringNotContainsString('Object.keys(products)', $editView);
        $this->assertStringContainsString('products.forEach(function(product)', $editView);
        $this->assertStringContainsString('product.id', $editView);
        $this->assertStringContainsString('product.name', $editView);
    }
}
