<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PurchaseFormLayoutTest extends TestCase
{
    /** @test */
    public function purchase_create_form_has_correct_3x4_grid_and_labels(): void
    {
        $file = __DIR__ . '/../../resources/views/purchase/create.blade.php';
        $this->assertFileExists($file);

        $contents = str_replace("\r\n", "\n", file_get_contents($file));

        // 1. Verifikasi grid col-sm-3 pada 12 kolom utama
        $this->assertStringContainsString('{{-- Row 1, Col 1: Purchase No --}}', $contents);
        $this->assertStringContainsString('{{-- Row 1, Col 2: Business Location --}}', $contents);
        $this->assertStringContainsString('{{-- Row 1, Col 3: Purchase Status --}}', $contents);
        $this->assertStringContainsString('{{-- Row 1, Col 4: Supplier --}}', $contents);
        $this->assertStringContainsString('{{-- Row 2, Col 1: P. Invoice No --}}', $contents);
        $this->assertStringContainsString('{{-- Row 2, Col 2: Received Date --}}', $contents);
        $this->assertStringContainsString('{{-- Row 2, Col 3: Invoice Date --}}', $contents);
        $this->assertStringContainsString('{{-- Row 2, Col 4: VAT Invoice ? --}}', $contents);
        $this->assertStringContainsString('{{-- Row 3, Col 1: Store --}}', $contents);
        $this->assertStringContainsString('{{-- Row 3, Col 2: Pay Term --}}', $contents);
        $this->assertStringContainsString('{{-- Row 3, Col 3: Attach Document --}}', $contents);
        $this->assertStringContainsString('{{-- Row 3, Col 4: Purchase order No --}}', $contents);

        // 2. Verifikasi 7 kolom memiliki label 'Required to Fill'
        // Purchase Status
        $this->assertStringContainsString("{!! Form::label('status', __('purchase.purchase_status') . ':*') !!}\n                        <span class=\"required-to-fill-label\">Required to Fill</span>", $contents);
        // Supplier
        $this->assertStringContainsString("{!! Form::label('supplier_id', __('purchase.supplier') . ':*') !!}\n                        <span class=\"required-to-fill-label\">Required to Fill</span>", $contents);
        // P. Invoice No
        $this->assertStringContainsString("{!! Form::label('ref_no', __('purchase.p_invoice_no') . ':') !!}\n                        <span class=\"required-to-fill-label\">Required to Fill</span>", $contents);
        // Received Date
        $this->assertStringContainsString("{!! Form::label('transaction_date', (!empty(\$is_purchase_order) ? 'Order Date' : __('purchase.purchase_date')) . ':*') !!}\n                        <span class=\"required-to-fill-label\">Required to Fill</span>", $contents);
        // Invoice Date
        $this->assertStringContainsString("{!! Form::label('invoice_date', __('purchase.invoice_date') . ':*') !!}\n                        <span class=\"required-to-fill-label\">Required to Fill</span>", $contents);
        // VAT Invoice ?
        $this->assertStringContainsString("{!! Form::label('is_vat', __('lang_v1.is_vat')) !!}\n                        <span class=\"required-to-fill-label\">Required to Fill</span>", $contents);
        // Store
        $this->assertStringContainsString("{!! Form::label('store_id', __('lang_v1.store_id') . ':*') !!}\n                        <span class=\"required-to-fill-label\">Required to Fill</span>", $contents);
    }

    /** @test */
    public function purchase_edit_form_has_correct_3x4_grid_and_labels(): void
    {
        $file = __DIR__ . '/../../resources/views/purchase/edit.blade.php';
        $this->assertFileExists($file);

        $contents = str_replace("\r\n", "\n", file_get_contents($file));

        // 1. Verifikasi grid col-sm-3 pada 12 kolom utama
        $this->assertStringContainsString('{{-- Row 1, Col 1: Purchase No --}}', $contents);
        $this->assertStringContainsString('{{-- Row 1, Col 2: Business Location --}}', $contents);
        $this->assertStringContainsString('{{-- Row 1, Col 3: Purchase Status --}}', $contents);
        $this->assertStringContainsString('{{-- Row 1, Col 4: Supplier --}}', $contents);
        $this->assertStringContainsString('{{-- Row 2, Col 1: P. Invoice No --}}', $contents);
        $this->assertStringContainsString('{{-- Row 2, Col 2: Received Date --}}', $contents);
        $this->assertStringContainsString('{{-- Row 2, Col 3: Invoice Date --}}', $contents);
        $this->assertStringContainsString('{{-- Row 2, Col 4: VAT Invoice ? --}}', $contents);
        $this->assertStringContainsString('{{-- Row 3, Col 1: Store --}}', $contents);
        $this->assertStringContainsString('{{-- Row 3, Col 2: Pay Term --}}', $contents);
        $this->assertStringContainsString('{{-- Row 3, Col 3: Attach Document --}}', $contents);
        $this->assertStringContainsString('{{-- Row 3, Col 4: Purchase order No --}}', $contents);

        // 2. Verifikasi 7 kolom memiliki label 'Required to Fill'
        // Purchase Status
        $this->assertStringContainsString("<span class=\"required-to-fill-label\">Required to Fill</span>\n                        {!! Form::label('status', __('purchase.purchase_status') . ':*') !!}", $contents);
        
        // Supplier
        $this->assertStringContainsString("<span class=\"required-to-fill-label\">Required to Fill</span>\n                        {!! Form::label('supplier_id', __('purchase.supplier') . ':*') !!}", $contents);
        
        // P. Invoice No
        $this->assertStringContainsString("<span class=\"required-to-fill-label\">Required to Fill</span>\n                        {!! Form::label('ref_no', __('purchase.p_invoice_no') . ':') !!}", $contents);
        
        // Received Date
        $this->assertStringContainsString("<span class=\"required-to-fill-label\">Required to Fill</span>\n                        {!! Form::label('transaction_date', __('purchase.purchase_date') . ':*') !!}", $contents);
        
        // Invoice Date
        $this->assertStringContainsString("<span class=\"required-to-fill-label\">Required to Fill</span>\n                        {!! Form::label('invoice_date', __('purchase.invoice_date') . ':*') !!}", $contents);
        
        // VAT Invoice ?
        $this->assertStringContainsString("<span class=\"required-to-fill-label\">Required to Fill</span>\n                        {!! Form::label('is_vat', __('lang_v1.is_vat')) !!}", $contents);
        
        // Store
        $this->assertStringContainsString("<span class=\"required-to-fill-label\">Required to Fill</span>\n                        {!! Form::label('store_id_display', __('lang_v1.store_id') . ':*') !!}", $contents);
    }
}
