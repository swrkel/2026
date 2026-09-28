<?php

namespace Tests\Feature;

use App\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ViewOfflineModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_ezyinvoice_add_view_contains_offline_mode_field()
    {
        // Arrange: create a business and set it in session
        $business = Business::create([
            'name' => 'Test Biz',
            'currency_precision' => 2,
        ]);

        $customers = [];
        $products = [];
        $invoice_no = 'INV-TEST-001';
        $ezyinvoice_credit_sale_payments = new Collection([]);
        $walkin = [];
        $only_walkin = false;

        // Act: render the EzyInvoice add view
        $html = $this
            ->withSession(['user.business_id' => $business->id])
            ->view('ezyinvoice::invoices.add', compact(
                'customers',
                'products',
                'invoice_no',
                'ezyinvoice_credit_sale_payments',
                'walkin',
                'only_walkin'
            ))
            ->render();

        // Assert: hidden offline_mode field exists
        $this->assertStringContainsString('name="offline_mode"', $html);
        $this->assertStringContainsString('id="offline_mode"', $html);
        $this->assertStringContainsString('value="0"', $html);
    }
}
