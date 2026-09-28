<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955DistributionChangedDetailsLayoutTest extends TestCase
{
    public function test_invoice_changed_details_modal_uses_titled_sections_and_lists(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $invoiceIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/invoices/index.blade.php');

        $this->assertIsString($invoiceIndex);
        $this->assertStringContainsString('Created Details', $invoiceIndex);
        $this->assertStringContainsString('Changed Details', $invoiceIndex);
        $this->assertStringContainsString('Deleted Details', $invoiceIndex);
        $this->assertStringContainsString('Sales Order Note Details', $invoiceIndex);
        $this->assertStringContainsString('id="modal_activity_created_list"', $invoiceIndex);
        $this->assertStringContainsString('id="modal_activity_changed_list"', $invoiceIndex);
        $this->assertStringContainsString('id="modal_activity_deleted_list"', $invoiceIndex);
        $this->assertStringContainsString('id="modal_activity_sales_order_note_list"', $invoiceIndex);
    }

    public function test_sales_order_changed_details_modal_uses_titled_sections_and_lists(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $salesOrderIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($salesOrderIndex);
        // New AJAX-based approach: modal placeholder class and JS handler
        $this->assertStringContainsString('so_activity_log_modal', $salesOrderIndex);
        $this->assertStringContainsString('view-so-changed-activities', $salesOrderIndex);
        // Partial view contains the section headings
        $partial = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/partials/activity_log_popup.blade.php');
        $this->assertStringContainsString('Changed Activities', $partial);
        $this->assertStringContainsString('Changed Details', $partial);
    }
}
