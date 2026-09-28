<?php

namespace Tests\Feature;

use Tests\TestCase;

class ViewPaymentActionLinkTest extends TestCase
{
    public function test_view_payment_links_do_not_use_broken_ledger_json_endpoint()
    {
        $projectRoot = base_path();

        // 1. Check vat_invoices/index.blade.php
        $vatInvoiceIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/vat_invoices/index.blade.php');
        $this->assertStringNotContainsString('contacts/ledger?contact_id=', $vatInvoiceIndex, 'vat_invoices/index.blade.php contains broken contacts/ledger link');
        $this->assertStringContainsString('contacts/\' . $inv->customer_id . \'?view=ledger', $vatInvoiceIndex, 'vat_invoices/index.blade.php should use html contact show ledger page');

        // 2. Check invoices/index.blade.php
        $invoiceIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/invoices/index.blade.php');
        $this->assertStringNotContainsString('contacts/ledger?contact_id=', $invoiceIndex, 'invoices/index.blade.php contains broken contacts/ledger link');
        $this->assertStringContainsString('contacts/\' . $inv->customer_id . \'?view=ledger', $invoiceIndex, 'invoices/index.blade.php should use html contact show ledger page');

        // 3. Check sales_orders/index.blade.php
        $salesOrderIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');
        $this->assertStringNotContainsString('contacts/ledger?contact_id=', $salesOrderIndex, 'sales_orders/index.blade.php contains broken contacts/ledger link');
        $this->assertStringNotContainsString('TransactionPaymentController@showPayments', $salesOrderIndex, 'sales_orders/index.blade.php uses non-existent TransactionPaymentController@showPayments');
        $this->assertStringContainsString('action(\'TransactionPaymentController@show\', [$so->linked_transaction_id])', $salesOrderIndex, 'sales_orders/index.blade.php should use TransactionPaymentController@show');
    }

    public function test_view_payment_button_is_unconditionally_below_view_in_sales_orders()
    {
        $projectRoot = base_path();
        $salesOrderIndex = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');
        
        // Assert that the invoices count check is not wrapping the view payment block
        $this->assertStringNotContainsString('@if($so->invoices->count() > 0)', $salesOrderIndex);
    }
}
