<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Transaction;
use App\Business;
use App\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DistributionInvoiceGuestViewTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_renders_the_distribution_invoice_guest_preview_successfully()
    {
        $user = User::first() ?: User::factory()->create();
        $business_id = $user->business_id;

        // 1. Create a dummy customer
        $contact_id = DB::table('contacts')->insertGetId([
            'business_id' => $business_id,
            'type' => 'customer',
            'name' => 'Dist Customer Test',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 2. Create a distribution invoice
        $invoice_id = DB::table('distribution_invoices')->insertGetId([
            'business_id' => $business_id,
            'customer_id' => $contact_id,
            'customer_name' => 'Dist Customer Test',
            'invoice_no' => 'DI-TEST-999',
            'date' => now(),
            'grand_total' => 1500.00,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 3. Create a transaction representing the distribution invoice
        $transaction_id = DB::table('transactions')->insertGetId([
            'business_id' => $business_id,
            'type' => 'sell',
            'sub_type' => null,
            'status' => 'final',
            'contact_id' => $contact_id,
            'transaction_date' => now(),
            'final_total' => 1500.00,
            'invoice_no' => 'DI-TEST-999',
            'invoice_token' => 'dummy_distribution_token_123',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 4. Request the public guest preview URL
        $response = $this->get('/distribution-invoice/dummy_distribution_token_123');

        // Asserts correct status and text presence
        $response->assertStatus(200);
        $response->assertSee('DI-TEST-999');
        $response->assertSee('Dist Customer Test');
    }

    /** @test */
    public function it_redirects_old_invoice_url_to_distribution_invoice_url()
    {
        $user = User::first() ?: User::factory()->create();
        $business_id = $user->business_id;

        // 1. Create a dummy customer
        $contact_id = DB::table('contacts')->insertGetId([
            'business_id' => $business_id,
            'type' => 'customer',
            'name' => 'Dist Customer Test Redirect',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 2. Create a distribution invoice
        $invoice_id = DB::table('distribution_invoices')->insertGetId([
            'business_id' => $business_id,
            'customer_id' => $contact_id,
            'customer_name' => 'Dist Customer Test Redirect',
            'invoice_no' => 'DI-TEST-REDR',
            'date' => now(),
            'grand_total' => 1500.00,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 3. Create a transaction representing the distribution invoice
        $transaction_id = DB::table('transactions')->insertGetId([
            'business_id' => $business_id,
            'type' => 'sell',
            'sub_type' => null,
            'status' => 'final',
            'contact_id' => $contact_id,
            'transaction_date' => now(),
            'final_total' => 1500.00,
            'invoice_no' => 'DI-TEST-REDR',
            'invoice_token' => 'dummy_redirect_token_123',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 4. Request the old guest preview URL
        $response = $this->get('/invoice/dummy_redirect_token_123');

        // Asserts correct redirect
        $response->assertRedirect('/distribution-invoice/dummy_redirect_token_123');
    }
}
