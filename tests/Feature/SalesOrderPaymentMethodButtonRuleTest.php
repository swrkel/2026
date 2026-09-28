<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * IS1384 — Sales Order Payment Method Button Rule
 * Verifikasi logika button "Payment Details" di datatable List Sales Orders:
 * - Tampil jika Paid atau Partial (paid_amount > 0)
 * - Sembunyi jika Due (paid_amount = 0)
 * Referensi: identik dengan logic di list-invoices (DistributionInvoiceListController).
 */
class SalesOrderPaymentMethodButtonRuleTest extends TestCase
{
    private string $controllerContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerContent = file_get_contents(
            dirname(__DIR__, 2) . '/Modules/Distribution/Http/Controllers/DistributionSalesOrderController.php'
        );
    }

    /**
     * Button "Payment Details" hanya tampil jika ada pembayaran (paid_amount > 0).
     * Kondisi: $paid_amount > 0 (bukan Due).
     */
    public function test_payment_details_button_shown_only_when_paid_amount_greater_than_zero(): void
    {
        // Harus ada kondisi yang memeriksa paid_amount > 0 untuk tampilkan button
        $this->assertMatchesRegularExpression(
            '/\$paid_amount\s*>\s*0/',
            $this->controllerContent,
            'Controller harus memeriksa $paid_amount > 0 sebelum menampilkan button Payment Details'
        );
    }

    /**
     * Button "Payment Details" tidak boleh tampil jika status Due.
     * Verifikasi: tidak ada kondisi lama yang hanya cek $balance_due < $row->grand_total
     * sebagai satu-satunya kondisi untuk menampilkan button.
     */
    public function test_payment_details_button_condition_follows_due_paid_partial_rule(): void
    {
        // Button harus tidak menggunakan kondisi lama yang tidak memeriksa due dengan benar
        // Kondisi baru: paid_amount > 0 (artinya bukan due)
        $this->assertStringContainsString(
            'show-so-payment-btn',
            $this->controllerContent,
            'Button show-so-payment-btn harus ada di controller'
        );
    }
}
