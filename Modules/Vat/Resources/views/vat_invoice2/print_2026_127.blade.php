@php
    $font_size = $receipt_details->font_size;
    $h_font_size = $receipt_details->header_font_size;
    $f_font_size = $receipt_details->footer_font_size;
    $b_font_size = $receipt_details->business_name_font_size;
    $i_font_size = $receipt_details->invoice_heading_font_size;
    $footer_top_margin = $receipt_details->footer_top_margin;
    $admin_invoice_footer = $receipt_details->admin_invoice_footer;
    $is_standard_vat_invoice2_print = !empty($is_standard_vat_invoice2_print);
    $tax = $receipt_details->tax_rate->amount ?? 0;

    $vat_decimal_config = $vat_decimal_config ?? [
        'unit_vat_no_of_decimals' => 2,
        'unit_vat_rounding_off_required' => false,
        'sub_total_no_of_decimals' => 2,
        'sub_total_rounding_off_required' => false,
    ];
    $currency = session('currency', []);
    $decimal_separator = $currency['decimal_separator'] ?? '.';
    $thousand_separator = $currency['thousand_separator'] ?? ',';

    $applyConfiguredDecimals = function ($value, $decimals, $roundingRequired) {
        $value = is_numeric($value) ? (float) $value : 0.0;
        $decimals = min(max((int) $decimals, 0), 10);
        if ($roundingRequired) {
            return round($value, $decimals, PHP_ROUND_HALF_UP);
        }
        $factor = pow(10, $decimals);
        return $value < 0 ? ceil($value * $factor) / $factor : floor($value * $factor) / $factor;
    };

    $formatConfiguredNumber = function ($value, $decimals, $roundingRequired) use ($applyConfiguredDecimals, $decimal_separator, $thousand_separator) {
        $decimals = min(max((int) $decimals, 0), 10);
        return number_format(
            $applyConfiguredDecimals($value, $decimals, (bool) $roundingRequired),
            $decimals,
            $decimal_separator,
            $thousand_separator
        );
    };

    $formatVatQty = function ($value) use ($formatConfiguredNumber, $vat_decimal_config) {
        return $formatConfiguredNumber(
            $value,
            $vat_decimal_config['unit_vat_no_of_decimals'],
            $vat_decimal_config['unit_vat_rounding_off_required']
        );
    };

    $formatVatSubTotal = function ($value) use ($formatConfiguredNumber, $vat_decimal_config) {
        return $formatConfiguredNumber(
            $value,
            $vat_decimal_config['sub_total_no_of_decimals'],
            $vat_decimal_config['sub_total_rounding_off_required']
        );
    };


    // Load invoice2 font settings
    $invoice2_setting_record = \Modules\Vat\Entities\VatInvoice2Setting::where(
        'business_id',
        request()->session()->get('user.business_id')
    )->first();
    $invoice2_settings = (object) json_decode(
        optional($invoice2_setting_record)->settings ?? json_encode([])
    );

    // Business location address for supplier block
    $business_location = $receipt_details->business_location;
    $business_address = '';
    if ($business_location) {
        $address_parts = array_filter([
            $business_location->city,
            $business_location->state,
            $business_location->zip_code,
            $business_location->country,
        ]);
        $business_address = implode(', ', $address_parts);
    }

    // Helper to strip any "Rs" prefix so we can add it ourselves
    $stripRs = function ($value) {
        return preg_replace('/Rs\s*/i', '', $value ?? '');
    };

    // Safe date formatter to avoid Carbon parse errors on '-' or invalid values
    /*
     * IS2146: dates print as MM/DD/YYYY.
     *
     * Both branches below used 'd/m/Y', so 3 April printed as 03/04/2026 and
     * 13 January as 13/01/2026 - day first. The requested format is month
     * first, so 'm/d/Y'.
     *
     * Changed in BOTH branches: one handles a Carbon instance, the other a
     * parsed string, and a value arriving either way must format the same.
     */
    $formatDate = function ($value) {
        if ($value instanceof \Carbon\Carbon) {
            return $value->format('m/d/Y');
        }

        if (empty($value) || $value === '-') {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('m/d/Y');
        } catch (\Exception $e) {
            return '-';
        }
    };

    $raw_total_with_vat = (float) ($issue_customer_bill->total_amount ?? 0);
    $raw_vat_amount = (float) ($issue_customer_bill->tax_amount ?? 0);
    $raw_total_without_vat = $raw_total_with_vat - $raw_vat_amount;
    $raw_final_total = $raw_total_with_vat + (float) ($issue_customer_bill->price_adjustment ?? 0);

    $total_without_vat = $formatVatSubTotal($raw_total_without_vat);
    $vat_amount = $formatVatSubTotal($raw_vat_amount);
    $total_with_vat = $formatVatSubTotal($raw_final_total);
@endphp

<style>
    @page {
        @if ($is_standard_vat_invoice2_print)
            /* Use the paper selected in the browser/printer and fill its usable
               width. This prevents an A5-sized invoice being shrunk inside an
               A4 preview while still fitting correctly when A5 is selected. */
            size: auto;
            margin: 7mm;
        @else
            size: A4 portrait;
            margin: 5px;
        @endif
    }

    html,
    body {
        padding: 0;
    }

    body {
        @if ($is_standard_vat_invoice2_print)
            width: 100%;
            max-width: 210mm;
            min-height: 0;
            margin: 0 auto;
            padding: 7mm;
            box-sizing: border-box;
        @else
            margin: 5px;
            padding: 20px 19px;
        @endif
        font-family: Calibri, Arial, sans-serif;
        color: #111;
        background: #fff;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    th, td {
        border: 1px solid #000;
        padding: 5px 6px;
    }

    .no-border {
        border: none !important;
    }

    .separator {
        border: 1px dotted black;
    }

    .signature-cell {
        border: none !important;
        text-align: center;
        padding: 0 40px;
    }

    .signature-cell hr {
        margin: 0;
    }

    .amount-value-cell {
        text-align: right;
        white-space: nowrap;
    }

    .amount-prefix-cell {
        width: 40px;
        text-align: left;
        font-weight: bold;
    }

    /* Inner tables (supplier / purchaser details) should not show vertical separators */
    .no-inner-border td,
    .no-inner-border th {
        border: none !important;
        padding: 2px 0;
    }

    /* Remove grid lines but keep alignment */
    .no-grid,
    .no-grid th,
    .no-grid td {
        border: none !important;
    }

    .no-grid th,
    .no-grid td {
        padding: 5px 6px;
    }

    /* Dynamic font size classes driven by VatInvoice2Setting */
    .header_size {
        font-size: @if (!empty($invoice2_settings->header_size)) {{ $invoice2_settings->header_size }}px @else 28px @endif !important;
    }
    .company_size {
        font-size: @if (!empty($invoice2_settings->company_size)) {{ $invoice2_settings->company_size }}px @else 26px @endif !important;
    }
    .address_size {
        font-size: @if (!empty($invoice2_settings->address_size)) {{ $invoice2_settings->address_size }}px @else 26px @endif !important;
    }
    .customer_size {
        font-size: @if (!empty($invoice2_settings->customer_size)) {{ $invoice2_settings->customer_size }}px @else 26px @endif !important;
    }
    .vat_size {
        font-size: @if (!empty($invoice2_settings->vat_size)) {{ $invoice2_settings->vat_size }}px @else 26px @endif !important;
    }
    .registration_size {
        font-size: @if (!empty($invoice2_settings->registration_size)) {{ $invoice2_settings->registration_size }}px @else 26px @endif !important;
    }
    .invoice_size {
        font-size: @if (!empty($invoice2_settings->invoice_size)) {{ $invoice2_settings->invoice_size }}px @else 26px @endif !important;
    }
    .date_size {
        font-size: @if (!empty($invoice2_settings->date_size)) {{ $invoice2_settings->date_size }}px @else 26px @endif !important;
    }
    .method_size {
        font-size: @if (!empty($invoice2_settings->method_size)) {{ $invoice2_settings->method_size }}px @else 26px @endif !important;
    }
    .thead_size {
        font-size: @if (!empty($invoice2_settings->thead_size)) {{ $invoice2_settings->thead_size }}px @else 26px @endif !important;
    }
    .tbody_size {
        font-size: @if (!empty($invoice2_settings->tbody_size)) {{ $invoice2_settings->tbody_size }}px @else 26px @endif !important;
    }
    .sub_size {
        font-size: @if (!empty($invoice2_settings->sub_size)) {{ $invoice2_settings->sub_size }}px @else 26px @endif !important;
    }
    .footer_size {
        font-size: @if (!empty($invoice2_settings->footer_size)) {{ $invoice2_settings->footer_size }}px @else 26px @endif !important;
    }
    .header-label {
        font-weight: bold;
    }
    .header-box {
        border: 1px solid #000;
        padding: 5px 6px;
    }
    .section-title {
        font-weight: bold;
        text-transform: uppercase;
    }
    .signature-label {
        margin-top: 10px;
        font-weight: bold;
    }
    .amount-label-cell {
        font-weight: bold;
    }

    .vat-invoice2-print-structure {
        width: 100% !important;
        max-width: none !important;
        margin: 0 auto !important;
        box-sizing: border-box;
        overflow: visible !important;
    }

    .invoice-primary-header {
        text-align: center;
        margin: 0 0 8px;
        padding: 0;
    }

    .invoice-primary-title {
        display: inline-block;
        border: 1px solid #000;
        padding: 4px 30px;
        font-weight: bold;
        line-height: 1.1;
    }

    .invoice-primary-business {
        margin-top: 7px;
        font-weight: 700;
        line-height: 1.15;
        text-transform: uppercase;
    }

    .invoice-primary-duplicate {
        margin-top: 4px;
        line-height: 1.1;
        font-style: italic;
    }

    .signature-space {
        height: 60px;
    }

    /* S546: clear, professional VAT Invoice2 print that fills the selected paper width. */
    .vat-invoice2-standard-a5 {
        width: 100% !important;
        max-width: none !important;
        min-height: 0 !important;
        margin: 0 auto !important;
        color: #000;
        /*
         * IS2146: every print font size DOUBLED (200%).
         *
         * The sheet was built around a 9px base with several smaller sizes
         * (7.8px, 8.4px, 8.8px) - legible on screen at full zoom but small on
         * paper. Each hardcoded size in this stylesheet is now twice what it
         * was, and the DEFAULTS on the configurable rules above went from 13px
         * to 26px.
         *
         * The configurable values themselves are untouched: where a business has
         * set its own size in Invoice 2 settings, that setting still wins. Only
         * the fallbacks changed, so a business that never configured sizes gets
         * the larger print while one that did keeps its own choices.
         */
        font-size: 18px;
        line-height: 1.22;
        letter-spacing: 0;
        box-sizing: border-box;
    }

    /* Use readable print sizes. The layout now expands with the selected paper,
       so it no longer needs extremely small text to stay inside a fixed 138 mm box. */
    .vat-invoice2-standard-a5 .header_size {
        font-size: 23px !important;
    }
    .vat-invoice2-standard-a5 .company_size,
    .vat-invoice2-standard-a5 .address_size,
    .vat-invoice2-standard-a5 .customer_size,
    .vat-invoice2-standard-a5 .vat_size,
    .vat-invoice2-standard-a5 .registration_size,
    .vat-invoice2-standard-a5 .invoice_size,
    .vat-invoice2-standard-a5 .date_size,
    .vat-invoice2-standard-a5 .method_size,
    .vat-invoice2-standard-a5 .sub_size {
        font-size: 18px !important;
    }
    .vat-invoice2-standard-a5 .thead_size {
        font-size: 15.6px !important;
    }
    .vat-invoice2-standard-a5 .tbody_size {
        font-size: 17.6px !important;
    }
    .vat-invoice2-standard-a5 .footer_size {
        font-size: 16.8px !important;
    }

    .vat-invoice2-standard-a5 table {
        table-layout: fixed;
    }

    .vat-invoice2-standard-a5 th,
    .vat-invoice2-standard-a5 td {
        box-sizing: border-box;
        border-color: #333;
        border-width: 0.7px;
        padding: 2px 3px;
        line-height: 1.18;
        overflow-wrap: normal;
        word-break: normal;
    }

    .vat-invoice2-standard-a5 .invoice-primary-header {
        margin: 0 0 7px !important;
        padding: 0 !important;
    }

    .vat-invoice2-standard-a5 .invoice-primary-title {
        padding: 3px 24px !important;
        border: 0.7px solid #333 !important;
        letter-spacing: 0.15px;
        line-height: 1.1;
    }

    .vat-invoice2-standard-a5 .invoice-primary-business {
        margin-top: 5px !important;
        font-size: 24px !important;
        line-height: 1.1;
    }

    .vat-invoice2-standard-a5 .invoice-primary-duplicate {
        margin-top: 3px !important;
        font-size: 18px !important;
        line-height: 1.1;
    }

    .vat-invoice2-standard-a5 .split-half-left {
        padding-right: 2px !important;
    }

    .vat-invoice2-standard-a5 .split-half-right {
        padding-left: 2px !important;
    }

    .vat-invoice2-standard-a5 .invoice-meta-table,
    .vat-invoice2-standard-a5 .invoice-party-table,
    .vat-invoice2-standard-a5 .invoice-delivery-table {
        margin-bottom: 7px !important;
    }

    .vat-invoice2-standard-a5 .invoice-additional-table {
        margin-bottom: 11px !important;
    }

    .vat-invoice2-standard-a5 .header-box {
        padding: 2px 3px;
        border: 0.7px solid #333 !important;
        vertical-align: middle;
    }

    .vat-invoice2-standard-a5 .invoice-meta-table .header-box,
    .vat-invoice2-standard-a5 .invoice-delivery-table .header-box {
        white-space: nowrap;
    }

    .vat-invoice2-standard-a5 .invoice-party-box {
        height: 25mm;
        vertical-align: top;
        padding: 3px 4px !important;
    }

    .vat-invoice2-standard-a5 .no-inner-border {
        table-layout: fixed;
    }

    .vat-invoice2-standard-a5 .no-inner-border td,
    .vat-invoice2-standard-a5 .no-inner-border th {
        padding: 1px 0;
        line-height: 1.12;
        vertical-align: top;
    }

    .vat-invoice2-standard-a5 .no-inner-border td:first-child {
        width: 39%;
        padding-right: 3px;
        white-space: nowrap;
    }

    .vat-invoice2-standard-a5 .no-inner-border td:last-child {
        width: 61%;
        overflow-wrap: anywhere;
    }

    .vat-invoice2-standard-a5 .invoice-items-table {
        width: 100%;
        table-layout: fixed;
        margin: 0 0 5px !important;
    }

    .vat-invoice2-standard-a5 .invoice-items-table th,
    .vat-invoice2-standard-a5 .invoice-items-table td {
        border: 0.7px solid #333 !important;
        padding: 2px 3px !important;
        vertical-align: middle;
    }

    .vat-invoice2-standard-a5 .invoice-items-table thead {
        display: table-header-group;
    }

    .vat-invoice2-standard-a5 .invoice-items-table thead th {
        line-height: 1.08;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .vat-invoice2-standard-a5 .invoice-items-table tbody td {
        min-height: 14px;
    }

    .vat-invoice2-standard-a5 .invoice-totals-table {
        margin: 0 0 9px !important;
    }

    .vat-invoice2-standard-a5 .invoice-totals-table td {
        padding: 2px 3px !important;
    }

    .vat-invoice2-standard-a5 .invoice-totals-table .amount-label-cell {
        width: 72%;
    }

    .vat-invoice2-standard-a5 .invoice-totals-table .amount-value-cell {
        width: 28%;
        text-align: right;
    }

    .vat-invoice2-standard-a5 .invoice-words-table {
        margin-bottom: 8px !important;
    }

    .vat-invoice2-standard-a5 .invoice-payment-table {
        margin-bottom: 16px !important;
    }

    .vat-invoice2-standard-a5 .invoice-signature-table {
        margin-top: 18px !important;
    }

    .vat-invoice2-standard-a5 .signature-cell {
        padding: 0 12px;
    }

    .vat-invoice2-standard-a5 .signature-space {
        height: 24px;
    }

    .vat-invoice2-standard-a5 .signature-label {
        margin-top: 4px;
        font-weight: 600;
    }

    .vat-invoice2-standard-a5 .separator {
        border: 0;
        border-top: 0.7px dotted #333;
    }

    .vat-invoice2-standard-a5 .invoice-items-table tr,
    .vat-invoice2-standard-a5 .invoice-totals-table,
    .vat-invoice2-standard-a5 .invoice-party-box,
    .vat-invoice2-standard-a5 .signature-cell {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    /*
     * VAT Print 2026 sizing requested in the marked sample:
     * 1. Use the supplier/purchaser detail font size throughout the invoice.
     * 2. Keep the TAX INVOICE and business-name block at exactly twice that size.
     * 3. Double the date/invoice-number row height.
     * 4. Double the vertical space of the lower invoice section.
     */
    .vat-invoice2-standard-a5,
    .vat-invoice2-standard-a5 table,
    .vat-invoice2-standard-a5 th,
    .vat-invoice2-standard-a5 td,
    .vat-invoice2-standard-a5 span,
    .vat-invoice2-standard-a5 div,
    .vat-invoice2-standard-a5 p {
        font-size: 18px !important;
        line-height: 1.25 !important;
    }

    .vat-invoice2-standard-a5 .invoice-primary-title,
    .vat-invoice2-standard-a5 .invoice-primary-business {
        font-size: 36px !important;
        line-height: 1.2 !important;
    }

    .vat-invoice2-standard-a5 .invoice-primary-duplicate {
        font-size: 18px !important;
    }

    /* No. 3: date and invoice-number columns at 200% of the earlier height. */
    .vat-invoice2-standard-a5 .invoice-meta-table .header-box {
        height: 8mm !important;
        min-height: 8mm !important;
        padding-top: 4px !important;
        padding-bottom: 4px !important;
        vertical-align: middle !important;
    }

    /* No. 4: increase the lower invoice rows/columns by 100%. */
    .vat-invoice2-standard-a5 .invoice-delivery-table .header-box,
    .vat-invoice2-standard-a5 .invoice-additional-table .header-box,
    .vat-invoice2-standard-a5 .invoice-words-table .header-box,
    .vat-invoice2-standard-a5 .invoice-payment-table .header-box {
        height: 8mm !important;
        min-height: 8mm !important;
        padding-top: 4px !important;
        padding-bottom: 4px !important;
        vertical-align: middle !important;
    }

    .vat-invoice2-standard-a5 .invoice-items-table th,
    .vat-invoice2-standard-a5 .invoice-items-table td,
    .vat-invoice2-standard-a5 .invoice-totals-table td {
        height: 7mm !important;
        min-height: 7mm !important;
        padding-top: 4px !important;
        padding-bottom: 4px !important;
        vertical-align: middle !important;
    }

    .vat-invoice2-standard-a5 .invoice-payment-table {
        margin-bottom: 32px !important;
    }

    .vat-invoice2-standard-a5 .invoice-signature-table {
        margin-top: 36px !important;
    }

    .vat-invoice2-standard-a5 .signature-space {
        height: 48px !important;
    }

    @media print {
        @if ($is_standard_vat_invoice2_print)
            html,
            body {
                width: 100% !important;
                max-width: none !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .vat-invoice2-print-structure,
            .vat-invoice2-standard-a5 {
                width: 100% !important;
                max-width: none !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                transform: none !important;
                zoom: 1 !important;
            }
        @else
            body {
                margin: 0 !important;
                padding: 5px 19px !important;
            }

            .vat-invoice2-print-structure {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
            }
        @endif
    }
</style>

<div class="vat-invoice2-print-structure {{ $is_standard_vat_invoice2_print ? 'vat-invoice2-standard-a5' : 'vat-invoice2-standard-a4' }}">
    {{-- Single compact invoice heading. The duplicate top heading/blank area is intentionally removed. --}}
    <div class="invoice-primary-header">
        <div class="header_size invoice-primary-title">TAX INVOICE</div>
        <div class="invoice-primary-business">
            {{ strtoupper($receipt_details->business_name ?? $receipt_details->display_name ?? '') }}
        </div>
        @if (!empty($vat_invoice_2_2026_print) && $vat_invoice_2_2026_print > 1)
            <div class="address_size invoice-primary-duplicate">
                Duplicate - {{ $vat_invoice_2_2026_print }}
            </div>
        @endif
    </div>

    {{-- Date of invoice and Tax invoice no: two separate boxes, same row, with visible gap --}}
    <table class="invoice-meta-table" style="width: 100%; border-collapse: collapse; border: none;">
        <tr>
            {{-- Create the gap without adding outer left/right spacing --}}
            <td class="split-half-left" style="border: none; padding: 0 10px 0 0; width: 50%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="header-box date_size">
                            <span class="header-label">Date of Invoice:</span>
                            <span>
                                {{ $formatDate($receipt_details->invoice_date ?? null) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="split-half-right" style="border: none; padding: 0 0 0 10px; width: 50%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="header-box invoice_size">
                            <span class="header-label">Tax Invoice No:</span>
                            <span>{{ $receipt_details->invoice_no ?? '-' }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Supplier info and Purchaser info: two separate boxes, same row, with visible gap --}}
    <table class="invoice-party-table" style="width:100%; border-collapse: collapse; border: none;">
        <tr>
            <td class="split-half-left" style="border: none; padding: 0 10px 0 0; width:50%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="header-box company_size invoice-party-box" style="vertical-align: top;">
                            <table class="no-inner-border" style="width: 100%;">
                                <tr>
                                    <td class="header-label">Supplier's TIN:</td>
                                    <td>{{ $receipt_details->suppliers_tin ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="header-label">Supplier's Name:</td>
                                    <td>
                                        {{ strtoupper($receipt_details->business_name ?? '') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="header-label">Address:</td>
                                    <td>
                                        {{ $business_address ?: ($receipt_details->address ?? '-') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="header-label">Telephone No:</td>
                                    <td>{{ $receipt_details->sup_telephone_no ?? '-' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="split-half-right" style="border: none; padding: 0 0 0 10px; width:50%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="header-box customer_size invoice-party-box" style="vertical-align: top;">
                            <table class="no-inner-border" style="width: 100%;">
                                <tr>
                                    <td class="header-label">Purchaser's TIN:</td>
                                    <td>{{ $receipt_details->customer->vat_number ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="header-label">Purchaser's Name:</td>
                                    <td>
                                        {{ strtoupper($receipt_details->customer_name ?? ($receipt_details->customer->name ?? '-')) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="header-label">Address:</td>
                                    <td>{{ $receipt_details->customer->address ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="header-label">Telephone No:</td>
                                    <td>
                                        {{ $receipt_details->customer->mobile
                                            ?? $receipt_details->customer->landline
                                            ?? '-' }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Date of delivery & Place of supply: two separate boxes, same row, with visible gap --}}
    <table class="invoice-delivery-table" style="width:100%; border-collapse: collapse; border: none;">
        <tr>
            <td class="split-half-left" style="border: none; padding: 0 10px 0 0; width:50%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="header-box date_size">
                            <span class="header-label">Date of Delivery:</span>
                            <span>
                                {{ $formatDate($receipt_details->delivery_date ?? null) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="split-half-right" style="border: none; padding: 0 0 0 10px; width:50%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="header-box address_size">
                            <span class="header-label">Place of Supply:</span>
                            <span>{{ $issue_customer_bill->place_of_supply ?? '-' }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Additional information (single full-width box, no vertical separator) --}}
    <table class="invoice-additional-table" style="width:100%; border-collapse: collapse;">
        <tr>
            <td class="header-box address_size">
                <span class="header-label">Additional Information If:</span>
                <span>{{ $issue_customer_bill->additional_information ?? '-' }}</span>
            </td>
        </tr>
    </table>

    {{-- Items table: bordered for the requested standard A5 print; legacy 127 remains unchanged. --}}
    <table class="invoice-items-table {{ $is_standard_vat_invoice2_print ? 'items-table' : 'no-grid' }}" style="margin-bottom: 0;">
        <thead>
            <tr>
                <th class="section-title thead_size" style="text-align: center; width: 16%;">REFERENCE</th>
                <th class="section-title thead_size" style="text-align: center; width: 34%;">DESCRIPTION OF GOODS SERVICES</th>
                <th class="section-title thead_size" style="text-align: center; width: 15%;">QUANTITY</th>
                <th class="section-title thead_size" style="text-align: center; width: 15%;">UNIT PRICE</th>
                <th class="section-title thead_size" style="text-align: center; width: 20%;">AMOUNT EXCLUDING VAT (RS.)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $line_total_excl_vat = 0;
            @endphp
            @forelse($bill_details as $line)
                @php
                    // Use stored tax/sub_total when available to derive amount excluding VAT
                    $line_tax = $line->tax ?? 0;
                    $line_sub_total = $line->sub_total ?? 0;
                    $amount_excl_vat = $line_sub_total - $line_tax;
                    $line_total_excl_vat += $amount_excl_vat;
                @endphp
                <tr>
                    <td class="tbody_size" style="text-align: center;">
                        {{ $receipt_details->reference_no ?? '-' }}
                    </td>
                    <td class="tbody_size">
                        {{ $line->product_description ?? $line->product_name ?? '-' }}
                    </td>
                    <td class="tbody_size" style="text-align: right;">
                        {{ $formatVatQty($line->qty) }}
                    </td>
                    <td class="tbody_size" style="text-align: right;">
                        {{ @num_format($line->unit_price_before_tax ?? $line->unit_price ?? 0) }}
                    </td>
                    <td class="tbody_size" style="text-align: right;">
                        {{ $formatVatSubTotal($amount_excl_vat) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">&nbsp;</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals section (no visible grid lines) --}}
    <table class="no-grid invoice-totals-table" style="margin-bottom: 10px; width:100%; border-collapse: collapse; border-top: none;">
        @unless ($is_standard_vat_invoice2_print)
            <tr>
                <td class="amount-label-cell sub_size" style="width: 70%;">
                    Total Value of Supply
                </td>
                <td class="amount-value-cell sub_size" style="width: 30%;">
                    Rs {{ $total_without_vat !== '' ? $total_without_vat : $formatVatSubTotal($line_total_excl_vat) }}
                </td>
            </tr>
        @endunless
        <tr>
            <td class="amount-label-cell sub_size">
                VAT Amount (Total Value Of Supply @ {{ $tax }}%)
            </td>
            <td class="amount-value-cell sub_size">
                Rs {{ $vat_amount }}
            </td>
        </tr>
        <tr>
            <td class="amount-label-cell sub_size">
                Total Amount Including VAT
            </td>
            <td class="amount-value-cell sub_size">
                Rs {{ $total_with_vat }}
            </td>
        </tr>
    </table>

    {{-- Amount in words (single full-width box, no vertical separator) --}}
    <table class="invoice-words-table" style="width:100%; border-collapse: collapse;">
        <tr>
            <td class="header-box sub_size">
                <span class="header-label">Total Amount in Words:</span>
                <span>
                    {{ $receipt_details->total_amount_words ?? '-' }}
                </span>
            </td>
        </tr>
    </table>

    {{-- Mode of payment (single full-width box, no vertical separator) --}}
    <table class="invoice-payment-table" style="width:100%; border-collapse: collapse;">
        <tr>
            <td class="header-box method_size">
                <span class="header-label">Mode of Payment:</span>
                <span>
                    {{ $payment->method ?? (($payment_details[0]->method ?? null) ? $payment_details[0]->method : '-') }}
                </span>
            </td>
        </tr>
    </table>

    {{-- Signature lines --}}
    <table class="invoice-signature-table" style="border: none;">
        <tr>
            <td class="signature-cell">
                <div class="signature-space"></div>
                <hr class="separator">
                <div class="signature-label footer_size">Prepared By</div>
            </td>
            <td class="signature-cell">
                <div class="signature-space"></div>
                <hr class="separator">
                <div class="signature-label footer_size">Checked By</div>
            </td>
            <td class="signature-cell">
                <div class="signature-space"></div>
                <hr class="separator">
                <div class="signature-label footer_size">Customer Signature</div>
            </td>
        </tr>
    </table>

    {{-- Global Report & Bill footer: intentionally excluded from standard VAT Invoice2 2026 only. --}}
    @if (!$is_standard_vat_invoice2_print && !empty($admin_invoice_footer))
        <div style="text-align: center; margin-top: 20px;">
            <p class="footer_size" style="margin-top: {{ !empty($footer_top_margin) ? $footer_top_margin : 10 }}px;">
                {!! $admin_invoice_footer !!}
            </p>
        </div>
    @endif
</div>

<script>
    (function () {
        'use strict';

        var printStarted = false;

        function openBrowserPrint() {
            if (printStarted) {
                return;
            }

            printStarted = true;

            var showPrintDialog = function () {
                window.focus();
                window.setTimeout(function () {
                    window.print();
                }, 250);
            };

            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(showPrintDialog, showPrintDialog);
            } else {
                showPrintDialog();
            }
        }

        if (document.readyState === 'complete') {
            openBrowserPrint();
        } else {
            window.addEventListener('load', openBrowserPrint, { once: true });
        }

        window.addEventListener('afterprint', function () {
            if (window.opener && !window.opener.closed) {
                window.close();
            }
        });
    })();
</script>
