<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrdersPartialPaymentDetailsTest extends TestCase
{
    public function test_sales_orders_list_shows_payment_details_button_for_any_paid_amount(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($indexView);
        $this->assertStringContainsString('@if(($firstInvoice->payment_total ?? 0) > 0)', $indexView);
        $this->assertStringNotContainsString('@if($firstInvoiceDue <= 0.009 && ($firstInvoice->payment_total ?? 0) > 0)', $indexView);
        $this->assertStringContainsString('Payment Details', $indexView);
    }
}
