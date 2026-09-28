<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class DistributionInvoiceSalesOrderNoteTest extends TestCase
{
    public function test_invoice_create_and_edit_views_contain_sales_order_note_fields(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $createView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/invoices/create.blade.php');
        $editView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/invoices/edit.blade.php');

        $this->assertIsString($createView);
        $this->assertIsString($editView);

        // Assert sales_order_note inputs exist in create.blade.php
        $this->assertStringContainsString('name="sales_order_note"', $createView);

        // Assert sales_order_note inputs exist in edit.blade.php
        $this->assertStringContainsString('name="sales_order_note"', $editView);
    }

    public function test_invoice_list_controller_returns_sales_order_note_in_json(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/Modules/Distribution/Http/Controllers/DistributionInvoiceListController.php';

        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        $this->assertIsString($contents);
        $this->assertStringContainsString("'sales_order_note' => \$invoice->sales_order_note", $contents);
    }
}
