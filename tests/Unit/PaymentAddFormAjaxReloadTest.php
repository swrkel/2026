<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PaymentAddFormAjaxReloadTest extends TestCase
{
    /** @test */
    public function payment_row_js_reloads_all_active_datatables(): void
    {
        $file = __DIR__ . '/../../resources/views/transaction_payment/payment_row.blade.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        // Verify that the AJAX success handler reloads other data tables
        $this->assertStringContainsString('purchase_table.ajax.reload()', $contents);
        $this->assertStringContainsString('sell_table.ajax.reload()', $contents);
        $this->assertStringContainsString('vat_purchase_table.ajax.reload()', $contents);
        $this->assertStringContainsString('purchase_order_table.ajax.reload()', $contents);
    }
}
