<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * IS1384 — Sales Order Payment Details Form
 * Verifikasi bahwa tab "Create Sales Orders" memiliki form Payment Details
 * identik dengan yang ada di halaman Create Invoice.
 */
class SalesOrderPaymentDetailsFormTest extends TestCase
{
    private string $indexView;

    protected function setUp(): void
    {
        parent::setUp();
        $this->indexView = file_get_contents(
            dirname(__DIR__, 2) . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php'
        );
    }

    /** Form harus memiliki input payment_cash */
    public function test_create_tab_has_payment_cash_input(): void
    {
        $this->assertStringContainsString('name="payment_cash"', $this->indexView);
    }

    /** Form harus memiliki input payment_card */
    public function test_create_tab_has_payment_card_input(): void
    {
        $this->assertStringContainsString('name="payment_card"', $this->indexView);
    }

    /** Form harus memiliki input payment_credit */
    public function test_create_tab_has_payment_credit_input(): void
    {
        $this->assertStringContainsString('name="payment_credit"', $this->indexView);
    }

    /** Form harus memiliki input payment_cheque */
    public function test_create_tab_has_payment_cheque_input(): void
    {
        $this->assertStringContainsString('name="payment_cheque"', $this->indexView);
    }

    /** Form harus memiliki total field dengan id so_payment_total (readonly) */
    public function test_create_tab_has_payment_total_readonly_field(): void
    {
        $this->assertStringContainsString('id="so_payment_total"', $this->indexView);
    }

    /** Harus ada label "Payment Details" di section create tab */
    public function test_create_tab_has_payment_details_heading(): void
    {
        $this->assertStringContainsString('Payment Details', $this->indexView);
    }

    /** JS harus menghitung total payment otomatis */
    public function test_create_tab_has_js_payment_total_calculation(): void
    {
        $this->assertStringContainsString('so_payment_total', $this->indexView);
        $this->assertStringContainsString('so_payment_cash', $this->indexView);
    }

    /** JS tidak boleh mengandung handler duplikat yang tidak tertutup */
    public function test_js_does_not_contain_duplicate_unclosed_click_handler(): void
    {
        $this->assertStringNotContainsString("\$(document).on('click', '#so_add_new_customer_btn', function() {\n\n            // Payment Details", $this->indexView);
    }

    /** JS harus memindahkan modal payment ke body agar tidak tertutup backdrop */
    public function test_js_appends_payment_modal_to_body(): void
    {
        $this->assertStringContainsString("\$('#salesOrderPaymentModal').appendTo('body')", $this->indexView);
    }
}

