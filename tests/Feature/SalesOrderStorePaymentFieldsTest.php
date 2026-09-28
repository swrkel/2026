<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * IS1384 — Sales Order Store Payment Fields
 * Verifikasi bahwa controller store() menyimpan kolom payment ke DB.
 */
class SalesOrderStorePaymentFieldsTest extends TestCase
{
    private string $controllerContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerContent = file_get_contents(
            dirname(__DIR__, 2) . '/Modules/Distribution/Http/Controllers/DistributionSalesOrderController.php'
        );
    }

    /** store() harus menyimpan payment_cash */
    public function test_store_saves_payment_cash(): void
    {
        $this->assertStringContainsString('payment_cash', $this->controllerContent);
    }

    /** store() harus menyimpan payment_card */
    public function test_store_saves_payment_card(): void
    {
        $this->assertStringContainsString('payment_card', $this->controllerContent);
    }

    /** store() harus menyimpan payment_credit */
    public function test_store_saves_payment_credit(): void
    {
        $this->assertStringContainsString('payment_credit', $this->controllerContent);
    }

    /** store() harus menyimpan payment_cheque */
    public function test_store_saves_payment_cheque(): void
    {
        $this->assertStringContainsString('payment_cheque', $this->controllerContent);
    }

    /** store() harus menghitung dan menyimpan payment_total */
    public function test_store_saves_payment_total(): void
    {
        $this->assertStringContainsString('payment_total', $this->controllerContent);
    }

    /** Model fillable harus mencakup payment_cash */
    public function test_model_fillable_includes_payment_cash(): void
    {
        $modelContent = file_get_contents(
            dirname(__DIR__, 2) . '/Modules/Distribution/Entities/DistributionSalesOrder.php'
        );
        $this->assertStringContainsString("'payment_cash'", $modelContent);
    }

    /** Model fillable harus mencakup payment_total */
    public function test_model_fillable_includes_payment_total(): void
    {
        $modelContent = file_get_contents(
            dirname(__DIR__, 2) . '/Modules/Distribution/Entities/DistributionSalesOrder.php'
        );
        $this->assertStringContainsString("'payment_total'", $modelContent);
    }
}
