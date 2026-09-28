<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955InvoiceChangedDetailsWrapperLayoutTest extends TestCase
{
    public function test_invoice_changed_details_wrapper_uses_dedicated_layout_class(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $invoiceIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/invoices/index.blade.php');

        $this->assertIsString($invoiceIndex);
        $this->assertStringContainsString('id="modal_changed_details_wrapper"', $invoiceIndex);
        $this->assertStringContainsString('class="changed-details-sections"', $invoiceIndex);
        $this->assertStringNotContainsString('style="display:none; margin-top:8px;"', $invoiceIndex);
    }
}
