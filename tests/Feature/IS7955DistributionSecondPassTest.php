<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955DistributionSecondPassTest extends TestCase
{
    public function test_distribution_models_log_create_update_and_delete_activity(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $invoiceModel = file_get_contents($projectRoot . '/Modules/Distribution/Entities/DistributionInvoice.php');
        $salesOrderModel = file_get_contents($projectRoot . '/Modules/Distribution/Entities/DistributionSalesOrder.php');

        $this->assertIsString($invoiceModel);
        $this->assertIsString($salesOrderModel);

        $this->assertStringContainsString('use Spatie\\Activitylog\\Traits\\LogsActivity;', $invoiceModel);
        $this->assertStringContainsString('use LogsActivity;', $invoiceModel);
        $this->assertStringContainsString('public function getActivitylogOptions()', $invoiceModel);

        $this->assertStringContainsString('use Spatie\\Activitylog\\Traits\\LogsActivity;', $salesOrderModel);
        $this->assertStringContainsString('use LogsActivity;', $salesOrderModel);
        $this->assertStringContainsString('public function getActivitylogOptions()', $salesOrderModel);
    }

    public function test_invoice_list_exposes_customer_payment_edit_action(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $invoiceIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/invoices/index.blade.php');

        $this->assertIsString($invoiceIndex);
        $this->assertStringContainsString('class="btn btn-xs btn-default edit_payment"', $invoiceIndex);
        $this->assertStringContainsString("action('CustomerPaymentController@edit'", $invoiceIndex);
        $this->assertStringContainsString("action('CustomerPaymentController@update'", $invoiceIndex);
        $this->assertStringContainsString('id="editPaymentModal"', $invoiceIndex);
        $this->assertStringContainsString('id="editPaymentForm"', $invoiceIndex);
    }

    public function test_invoice_list_uses_data_driven_activity_payload_fields(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $invoiceIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/invoices/index.blade.php');
        $invoiceController = file_get_contents($projectRoot . '/Modules/Distribution/Http/Controllers/DistributionInvoiceController.php');

        $this->assertIsString($invoiceIndex);
        $this->assertIsString($invoiceController);
        $this->assertStringContainsString('data-activity-created=', $invoiceIndex);
        $this->assertStringContainsString('data-activity-changed=', $invoiceIndex);
        $this->assertStringContainsString('data-activity-deleted=', $invoiceIndex);
        $this->assertStringContainsString('use Spatie\\Activitylog\\Models\\Activity;', $invoiceController);
    }

    public function test_sales_order_list_uses_structured_activity_sections_and_payload_fields(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $salesOrderIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');
        $salesOrderController = file_get_contents($projectRoot . '/Modules/Distribution/Http/Controllers/DistributionSalesOrderController.php');

        $this->assertIsString($salesOrderIndex);
        $this->assertIsString($salesOrderController);
        // New AJAX-based Changed Activities
        $this->assertStringContainsString('so_activity_log_modal', $salesOrderIndex);
        $this->assertStringContainsString('view-so-changed-activities', $salesOrderIndex);
        $this->assertStringNotContainsString('id="so_modal_changed_activities"', $salesOrderIndex);
        $this->assertStringContainsString('use Spatie\\Activitylog\\Models\\Activity;', $salesOrderController);
    }
}
