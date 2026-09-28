<?php

namespace Tests\Feature;

use App\User;
use App\Business;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\Distribution\Entities\DistributionNumberingPrefix;

class DistributionNumberingPrefixTest extends TestCase
{
    use DatabaseTransactions;

    public function test_prefix_can_be_stored_and_displayed_for_all_types(): void
    {
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();

        // Ensure we delete any existing matching types to start clean
        DistributionNumberingPrefix::where('business_id', $business->id)
            ->whereIn('numbering_type', ['sales_invoice', 'stock_transfer', 'vat_distribution_invoice'])
            ->delete();

        // 1. Save sales_invoice (starts with s)
        $responseSI = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $business->id,
                'user.id' => $user->id,
            ])
            ->post('/distribution/prefix-numbering', [
                'numbering_type' => 'sales_invoice',
                'prefix' => 'SI',
                'starting_no' => 1,
            ]);
        $responseSI->assertStatus(302);

        // 2. Save stock_transfer (also starts with s)
        $responseST = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $business->id,
                'user.id' => $user->id,
            ])
            ->post('/distribution/prefix-numbering', [
                'numbering_type' => 'stock_transfer',
                'prefix' => 'DST',
                'starting_no' => 1,
            ]);
        $responseST->assertStatus(302);

        $prefixDST = DistributionNumberingPrefix::where('business_id', $business->id)
            ->where('numbering_type', 'stock_transfer')
            ->first();

        $this->assertNotNull($prefixDST, 'Prefix for stock_transfer was not saved.');
        $this->assertEquals('stock_transfer', $prefixDST->numbering_type, 'numbering_type is not stock_transfer in DB.');
        $this->assertEquals('DST', $prefixDST->prefix);

        // 3. Save vat_distribution_invoice
        $responseVat = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $business->id,
                'user.id' => $user->id,
            ])
            ->post('/distribution/prefix-numbering', [
                'numbering_type' => 'vat_distribution_invoice',
                'prefix' => 'VDI',
                'starting_no' => 1,
            ]);
        $responseVat->assertStatus(302);

        $prefixVat = DistributionNumberingPrefix::where('business_id', $business->id)
            ->where('numbering_type', 'vat_distribution_invoice')
            ->first();

        $this->assertNotNull($prefixVat, 'Prefix for vat_distribution_invoice was not saved.');
        $this->assertEquals('vat_distribution_invoice', $prefixVat->numbering_type, 'numbering_type is not vat_distribution_invoice in DB.');
        $this->assertEquals('VDI', $prefixVat->prefix);

        // 4. Request index with AJAX (Datatable)
        $ajaxResponse = $this->actingAs($user)
            ->withSession([
                'user.business_id' => $business->id,
                'user.id' => $user->id,
            ])
            ->getJson('/distribution/prefix-numbering', [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]);

        $ajaxResponse->assertStatus(200);
        $data = $ajaxResponse->json('data');

        $salesInvoiceRow = collect($data)->firstWhere('prefix', 'SI');
        $this->assertNotNull($salesInvoiceRow, 'Sales Invoice row not found in datatable output.');
        $this->assertEquals('Sales Invoice', $salesInvoiceRow['numbering_type']);

        $stockTransferRow = collect($data)->firstWhere('prefix', 'DST');
        $this->assertNotNull($stockTransferRow, 'Stock Transfer row not found in datatable output.');
        $this->assertEquals('Stock Transfer', $stockTransferRow['numbering_type'], 'Stock Transfer numbering_type label is incorrect.');

        $vatInvoiceRow = collect($data)->firstWhere('prefix', 'VDI');
        $this->assertNotNull($vatInvoiceRow, 'VAT Distribution Invoice row not found in datatable output.');
        $this->assertEquals('VAT Distribution Invoice', $vatInvoiceRow['numbering_type'], 'VAT Distribution Invoice numbering_type label is incorrect.');
    }
}
