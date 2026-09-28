<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955DistributionPolishPassTest extends TestCase
{
    public function test_distribution_activity_formatting_uses_business_friendly_labels(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $invoiceController = file_get_contents($projectRoot . '/Modules/Distribution/Http/Controllers/DistributionInvoiceController.php');
        $salesOrderController = file_get_contents($projectRoot . '/Modules/Distribution/Http/Controllers/DistributionSalesOrderController.php');

        $this->assertIsString($invoiceController);
        $this->assertIsString($salesOrderController);

        $this->assertStringContainsString("'invoice_no' => 'Invoice No'", $invoiceController);
        $this->assertStringContainsString("'delivery_date' => 'Delivery Date'", $invoiceController);
        $this->assertStringContainsString("'customer_name' => 'Customer Name'", $invoiceController);
        $this->assertStringContainsString("'customer_contact' => 'Customer Contact Number'", $invoiceController);
        $this->assertStringContainsString("'customer_address' => 'Location'", $invoiceController);
        $this->assertStringContainsString("'grand_total' => 'Total Amount'", $invoiceController);
        $this->assertStringContainsString("'payment_total' => 'Total Paid'", $invoiceController);
        $this->assertStringContainsString("'shipping_status' => 'Shipping Status'", $invoiceController);

        $this->assertStringContainsString("'sales_order_no' => 'Sales Order No'", $salesOrderController);
        $this->assertStringContainsString("'status' => 'Sales Order Status'", $salesOrderController);
        $this->assertStringContainsString("'shipping_status' => 'Shipping Status'", $salesOrderController);
    }

    public function test_invoice_edit_payment_modal_contains_reference_and_method_specific_fields(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $invoiceIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/invoices/index.blade.php');

        $this->assertIsString($invoiceIndex);
        $this->assertStringContainsString('id="edit_payment_ref_no"', $invoiceIndex);
        $this->assertStringContainsString('id="edit_card_number"', $invoiceIndex);
        $this->assertStringContainsString('id="edit_cheque_number"', $invoiceIndex);
        $this->assertStringContainsString('id="edit_bank_name"', $invoiceIndex);
        $this->assertStringContainsString('id="edit_payment_method_details"', $invoiceIndex);
        $this->assertStringContainsString("$('#edit_payment_ref_no').val(p.payment_ref_no || '')", $invoiceIndex);
        $this->assertStringContainsString("$('#edit_card_number').val(p.card_number || '')", $invoiceIndex);
        $this->assertStringContainsString("$('#edit_cheque_number').val(p.cheque_number || '')", $invoiceIndex);
        $this->assertStringContainsString("$('#edit_bank_name').val(p.bank_name || '')", $invoiceIndex);
        $this->assertStringContainsString('function toggleEditPaymentMethodFields()', $invoiceIndex);
    }
}
