<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class ListInvoicesToolbarTest extends TestCase
{
    public function test_list_invoices_page_uses_standard_datatable_export_button_partial(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/Modules/Distribution/Resources/views/invoices/list_invoices.blade.php';

        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        $this->assertIsString($contents);
        $this->assertStringContainsString("layouts.partials.datatable_export_button", $contents, 'list_invoices page should use the standard datatable_export_button layout partial.');
        $this->assertStringNotContainsString("dom: 'Bfrtip'", $contents, 'list_invoices page should not use custom dom: Bfrtip.');
    }
}
