<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrderCreateNotesFieldsTest extends TestCase
{
    public function test_create_forms_contain_notes_fields_and_location_label(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $createView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/create.blade.php');
        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($createView);
        $this->assertIsString($indexView);

        // Assert notes inputs exist in create.blade.php
        $this->assertStringContainsString('name="shipping_note"', $createView);
        $this->assertStringContainsString('name="invoice_note"', $createView);

        // Assert notes inputs exist in index.blade.php
        $this->assertStringContainsString('name="shipping_note"', $indexView);
        $this->assertStringContainsString('name="invoice_note"', $indexView);

        // Assert Location label in create.blade.php
        $this->assertStringContainsString('Location:', $createView);
        $this->assertStringNotContainsString('Customer Address:', $createView);

        // Assert fallback address in option data-address
        $this->assertStringContainsString('data-address="{{ $customer->landmark ?: ($customer->address_line_1 ?: ($customer->address ?: \'\')) }}"', $createView);
    }
}
