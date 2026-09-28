<?php

namespace Tests\Feature\Vat;

use Tests\TestCase;
use Modules\Vat\Entities\VatInvoice2;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class VatInvoice2ActivityLogTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_can_successfully_save_vat_invoice_2_and_log_activity()
    {
        $invoice = VatInvoice2::create([
            'business_id' => 1,
            'customer_id' => 1,
            'customer_bill_no' => 'VAT-TEST-ACT-1',
            'total_amount' => 1000,
            'date' => '2026-05-21',
            'created_by' => 1
        ]);

        $this->assertDatabaseHas('vat_invoices_2', [
            'customer_bill_no' => 'VAT-TEST-ACT-1'
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => VatInvoice2::class,
            'subject_id' => $invoice->id,
            'event' => 'created'
        ]);

        // Simulating the controller auto-post behavior
        \Modules\Vat\Http\Controllers\VatInvoiceToTransactionController::autoPostVatInvoiceTransactions($invoice);
    }
}
