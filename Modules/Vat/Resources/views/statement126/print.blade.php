@php
    $font_size = $receipt_details->font_size;
    $h_font_size = $receipt_details->header_font_size;
    $f_font_size = $receipt_details->footer_font_size;
    $b_font_size = $receipt_details->business_name_font_size;
    $i_font_size = $receipt_details->invoice_heading_font_size;
    $footer_top_margin = $receipt_details->footer_top_margin;
    $admin_invoice_footer = $receipt_details->admin_invoice_footer;
    $logo_height = $receipt_details->logo_height;
    $logo_width = $receipt_details->logo_width;
    $logo_margin_top = $receipt_details->logo_margin_top;
    $logo_margin_bottom = $receipt_details->logo_margin_bottom;
    $header_align = $receipt_details->header_align;
    $contact_details = $receipt_details->contact_details;
    $tax = $receipt_details->tax_rate->amount ?? 0;

    $report_name =
        \Modules\Vat\Entities\VatSetting::where('business_id', request()->session()->get('user.business_id'))
            ->where('status', 1)
            ->first()->tax_report_name ?? 'vat';

    $invoice2_settings =
        \Modules\Vat\Entities\VatInvoice2Setting::where(
            'business_id',
            request()->session()->get('user.business_id'),
        )->first()->settings ?? json_encode([]);
    $invoice2_settings = (object) json_decode($invoice2_settings);

@endphp

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/paper-css/0.3.0/paper.css">
<style>
    .header-section {
        line-height: 20px !important;
    }

    @page {
        size: A4 potrait;
        margin: 5px;
    }

    body {
        margin: 0;
        padding: 60px 25px 10px 25px;
    }

    .A4 h2, .A4 h4, .A4 h5, .A4 p {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

    .no-print {
        margin: 20px;
        text-align: center;
    }

    .no-print button {
        padding: 10px 30px;
        font-size: 16px;
        cursor: pointer;
        background-color: #d9534f;
        color: white;
        border: none;
        border-radius: 4px;
    }

    .no-print button:hover {
        background-color: #c9302c;
    }

    .duplicate-label {
        color: red;
        font-weight: bold;
        border: 3px solid red;
        padding: 5px 20px;
        display: inline-block;
        font-size: 24px;
        margin-bottom: 10px;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        .header-section {
            line-height: 20px !important;
            font-size: {{ $i_font_size }}px !important;
        }

        .row {
            page-break-inside: avoid;
        }

        .zero-padding {
            padding-bottom: 0px !important;
            padding-top: 0px important;
        }

        .text-bold {
            font-weight: bold;
        }

        .bordered {
            border: 1px solid black;
        }

        .separator {
            border: 1px dotted black;
        }

        .pad-50 {
            padding: 50px;
        }

        .border-none {
            border-top: 1px solid white;
            border-bottom: 1px solid white;
            border-left: 1px solid white;
        }

        .border-none-bottom {
            border-top: 1px solid white;
            border-left: 1px solid white;
        }

        .border-bottom-only {
            border-top: 1px solid white;
            border-bottom: 1px solid black;
            border-left: 1px solid white;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .header_size {
            font-size: @if (!empty($invoice2_settings->header_size))
                {{ $invoice2_settings->header_size }}px !important;
            @else
                18px !important;
            @endif
        }

        .company_size {
            font-size: @if (!empty($invoice2_settings->company_size))
                {{ $invoice2_settings->company_size }}px !important;
            @else
                18px !important;
            @endif
        }

        .address_size {
            font-size: @if (!empty($invoice2_settings->address_size))
                {{ $invoice2_settings->address_size }}px !important;
            @else
                18px !important;
            @endif
        }

        .customer_size {
            font-size: @if (!empty($invoice2_settings->customer_size))
                {{ $invoice2_settings->customer_size }}px !important;
            @else
                18px !important;
            @endif
        }

        .vat_size {
            font-size: @if (!empty($invoice2_settings->vat_size))
                {{ $invoice2_settings->vat_size }}px !important;
            @else
                18px !important;
            @endif
        }

        .method_size {
            font-size: @if (!empty($invoice2_settings->method_size))
                {{ $invoice2_settings->method_size }}px !important;
            @else
                18px !important;
            @endif
        }

        .registration_size {
            font-size: @if (!empty($invoice2_settings->registration_size))
                {{ $invoice2_settings->registration_size }}px !important;
            @else
                18px !important;
            @endif
        }

        .invoice_size {
            font-size: @if (!empty($invoice2_settings->invoice_size))
                {{ $invoice2_settings->invoice_size }}px !important;
            @else
                18px !important;
            @endif
        }

        .date_size {
            font-size: @if (!empty($invoice2_settings->date_size))
                {{ $invoice2_settings->date_size }}px !important;
            @else
                18px !important;
            @endif
        }

        .thead_size {
            font-size: @if (!empty($invoice2_settings->thead_size))
                {{ $invoice2_settings->thead_size }}px !important;
            @else
                18px !important;
            @endif
        }

        .tbody_size {
            font-size: @if (!empty($invoice2_settings->tbody_size))
                {{ $invoice2_settings->tbody_size }}px !important;
            @else
                18px !important;
            @endif
        }

        .sub_size {
            font-size: @if (!empty($invoice2_settings->sub_size))
                {{ $invoice2_settings->sub_size }}px !important;
            @else
                18px !important;
            @endif
        }

        .footer_size {
            font-size: @if (!empty($invoice2_settings->footer_size))
                {{ $invoice2_settings->footer_size }}px !important;
            @else
                18px !important;
            @endif
        }

    }
</style>

<!-- Your existing HTML content here -->


<!-- Your existing HTML content here -->

<div class="A4" style="width: 100% !important;">
    @if($is_duplicate)
    <div class="row" style="margin-bottom: 10px;">
        <div class="col-xs-12 text-center">
            <span class="duplicate-label">DUPLICATE</span>
        </div>
    </div>
    @endif
    <div style="text-align: center; margin-bottom: 5px;">
        <h2 class="uppercase header_size" style="margin-bottom: 6px !important;">@lang('vat::lang.' . $report_name) @lang('vat::lang.invoice')</h2>
        <h4 class="company_size" style="line-height: 1.3;">{{ strtoupper($receipt_details->display_name) }}</h4>
        <h5 class="address_size" style="line-height: 1.3;">{!! strtoupper($receipt_details->address) !!}</h5>
        <h5 class="address_size" style="line-height: 1.3;">{{ strtoupper($receipt_details->contact) }}</h5>
    </div>

    {{-- Top customer/info box: 3 visual columns (left info, blank spacer, right info) with no middle border --}}
    <table style="width:100%; border-collapse: collapse; border: 1px solid #000; margin-bottom: 0; margin-top: 0;">
        <tr>
            {{-- Left: customer details --}}
            <td style="width: 55%; padding: 6px 8px; border-right: none; vertical-align: top;">
                <h5 class="customer_size" style="line-height: 1.4;">
                    <strong>@lang('vat::lang.customer'):</strong> {{ strtoupper($receipt_details->customer_name) }}
                </h5>
                <h5 class="customer_size" style="line-height: 1.4;">
                    <strong>@lang('vat::lang.address'):</strong> {{ $receipt_details->customer->address }}
                </h5>
                <h5 class="vat_size" style="line-height: 1.4;">
                    <strong>@lang('contact.vat_number'):</strong> {{ $receipt_details->customer->vat_number }}
                </h5>
            </td>
            {{-- Middle: blank spacer, no visible vertical border --}}
            <td style="width: 5%; padding: 8px; border-left: none; border-right: none;">
                &nbsp;
            </td>
            {{-- Right: registration / invoice / date all on one line each --}}
            <td style="width: 40%; padding: 6px 8px; border-left: 1px solid #000; vertical-align: top;">
                <h5 class="registration_size" style="line-height: 1.4; white-space: nowrap;">
                    <strong>@lang('vat::lang.vat_registration_no'):</strong> {{ $receipt_details->tax_info1 }}
                </h5>
                <h5 class="invoice_size" style="line-height: 1.4; white-space: nowrap;">
                    <strong>@lang('vat::lang.invoice') #:</strong> {{ $receipt_details->invoice_no }}
                </h5>
                <h5 class="date_size" style="line-height: 1.4; white-space: nowrap;">
                    <strong>@lang('vat::lang.invoice_date'):</strong> {{ $receipt_details->invoice_date ? \Carbon\Carbon::parse($receipt_details->invoice_date)->format('d.m.Y H:i') : '-' }}
                </h5>
            </td>
        </tr>
    </table>

    <h5 class="method_size" style="line-height: 1.4; margin-top: 2px !important;">
        <strong>@lang('vat::lang.payment_method'):</strong>
        @foreach ($payment_details as $pmt)
            {{ ucfirst(str_replace('_', ' ', $pmt->method)) }}@if (!$loop->last),@endif
        @endforeach
    </h5>

    {{-- Main table: item details --}}
    <table style="width:100%; border-collapse: collapse; margin-top: 20px;">
        <tr style="background-color: #f5f5f5;">
            <td class="thead_size uppercase text-center text-bold" style="width: 12%; padding: 6px 4px; border: 1px solid #ddd;">
                Date
            </td>
            <td class="thead_size uppercase text-center text-bold" style="width: 33%; padding: 6px 4px; border: 1px solid #ddd; border-left: none;">
                Description
            </td>
            <td class="thead_size uppercase text-center text-bold" style="width: 10%; padding: 6px 4px; border: 1px solid #ddd; border-left: none;">
                QTY
            </td>
            <td class="thead_size uppercase text-center text-bold" style="width: 15%; padding: 6px 4px; border: 1px solid #ddd; border-left: none;">
                SALE INC. TAX
            </td>
            <td class="thead_size uppercase text-center text-bold" style="width: 20%; padding: 6px 4px; border: 1px solid #ddd; border-left: none;">
                Amount
            </td>
        </tr>

        @forelse($statement_details as $line)
            <tr>
                <td class="tbody_size text-center" style="padding: 6px 4px; border: 1px solid #ddd; border-top: none;">
                    {{ $receipt_details->invoice_date }}
                </td>
                <td class="tbody_size" style="padding: 6px 4px; word-break: break-all; border: 1px solid #ddd; border-left: none; border-top: none;">
                    {{ strtoupper($line->product_name) }}
                </td>
                <td class="tbody_size text-right" style="padding: 6px 4px; border: 1px solid #ddd; border-left: none; border-top: none; text-align: right;">
                    {{ @num_format($line->qty) }}
                </td>
                <td class="tbody_size text-right" style="padding: 6px 4px; border: 1px solid #ddd; border-left: none; border-top: none; text-align: right;">
                    {{ @num_format($line->unit_price ?? $line->unit_price_before_tax) }}
                </td>
                <td class="tbody_size text-right" style="padding: 6px 4px; border: 1px solid #ddd; border-left: none; border-top: none; text-align: right;">
                    {{ @num_format($line->sub_total) }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="padding: 8px 5px;">&nbsp;</td>
            </tr>
        @endforelse
    </table>

    {{-- Totals block (separate table, aligned to the right, with a light table structure) --}}
    <table style="width: 40%; border-collapse: collapse; margin-top: 6px; margin-left: auto;">
        <tr>
            <td class="sub_size text-left"
                style="padding: 6px 6px; background-color: #f5f5f5; width: 60%; border: 1px solid #ddd;">
                @lang('vat::lang.tax_base_value')
            </td>
            <td class="sub_size text-right"
                style="padding: 6px 6px; background-color: #f5f5f5; width: 40%; border: 1px solid #ddd; border-left: none; text-align: right;">
                {{ $receipt_details->total }}
            </td>
        </tr>
        <tr>
            <td class="sub_size text-left" style="padding: 6px 6px; border: 1px solid #ddd;">
                @lang('vat::lang.vat')
                @if (!empty($receipt_details->tax_rate))
                    ({{ $receipt_details->tax_rate->amount }}%)
                @endif
            </td>
            <td class="sub_size text-right" style="padding: 6px 6px; border: 1px solid #ddd; border-left: none; text-align: right;">
                {{ $receipt_details->total_vat }}
            </td>
        </tr>
        <tr>
            <td class="sub_size text-left" style="padding: 6px 6px; border: 1px solid #ddd;">
                @lang('vat::lang.price_adjustment')
            </td>
            <td class="sub_size text-right" style="padding: 6px 6px; border: 1px solid #ddd; border-left: none; text-align: right;">
                {{ $receipt_details->price_adjustment }}
            </td>
        </tr>
        <tr>
            <td class="sub_size text-left"
                style="padding: 6px 6px; background-color: #f5f5f5; border: 1px solid #ddd;">
                <strong>@lang('vat::lang.total_invoice_amount_with_vat')</strong>
            </td>
            <td class="sub_size text-right"
                style="padding: 6px 6px; background-color: #f5f5f5; border: 1px solid #ddd; border-left: none; text-align: right;">
                <strong>{{ $receipt_details->final_total }}</strong>
            </td>
        </tr>
    </table>

    <div style="height: 10px;"></div>

    <div class="row" style="margin-top: 10px;">
        <div class="footer_size col-xs-12 text-center">
            <table style="width:100%; border-collapse: collapse;">
                <tr>
                    <td style="width:33%; padding:0 20px; text-align:center;">
                        <div style="height: 30px;"></div>
                        <hr class="separator">
                        <h5 style="margin-top: 5px;"><strong>@lang('vat::lang.prepared_by')</strong></h5>
                    </td>
                    <td style="width:33%; padding:0 20px; text-align:center;">
                        <div style="height: 30px;"></div>
                        <hr class="separator">
                        <h5 style="margin-top: 5px;"><strong>@lang('vat::lang.checked_by')</strong></h5>
                    </td>
                    <td style="width:33%; padding:0 20px; text-align:center;">
                        <div style="height: 30px;"></div>
                        <hr class="separator">
                        <h5 style="margin-top: 5px;"><strong>@lang('vat::lang.customer_signature')</strong></h5>
                    </td>
                </tr>
            </table>
        </div>
    </div>


    @if (!empty($admin_invoice_footer))
        <div style="margin-top: 40px;">
            <div class="col-xs-12 text-center">
                <p class="centered"
                    style="font-size: {{ $f_font_size }}px !important; margin-top: @if (!empty($footer_top_margin)) {{ $footer_top_margin }}px; @else 10px; @endif">
                    {!! $admin_invoice_footer !!}
                </p>
            </div>
        </div>
    @endif
</div>

<!-- Close Button (hidden during print) -->
<div class="no-print">
    <button onclick="closePrintWindow()">Close</button>
</div>

<script>
    // Automatically open the browser print dialog when this page loads
    window.onload = function () {
        window.print();
    };

    // After printing (or cancelling), navigate back to the previous page
    window.onafterprint = function () {
        closePrintWindow();
    };

    function closePrintWindow() {
        // For tabs opened via navigation, go back to the listing/form
        if (window.history.length > 1) {
            window.history.back();
        } else {
            // Fallback: try to close if it was opened as a popup
            window.close();
        }
    }
</script>
