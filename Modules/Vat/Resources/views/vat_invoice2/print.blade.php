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

    // S733: Service invoices must not print Qty or Unit Cost/Unit Price in any print format.
    $normalized_sale_type = strtolower(trim((string) ($issue_customer_bill->sale_type ?? '')));
    $is_service_invoice = $normalized_sale_type === 'service';

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
        size: A4 portrait;
        margin: 5px;
    }

    body {
        margin: 0;
        padding: 15px 40px 15px 40px;
    }

    .A4 h2, .A4 h4, .A4 h5, .A4 p {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

    @media print {


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
    <div style="text-align: center; margin-bottom: 5px;">
        <h4 class="company_size" style="line-height: 1.3;">{{ strtoupper($receipt_details->display_name) }}</h4>
        <h5 class="address_size" style="line-height: 1.3;">{!! strtoupper($receipt_details->address) !!}</h5>
        <h5 class="address_size" style="line-height: 1.3;">{{ strtoupper($receipt_details->contact) }}</h5>
        <h2 class="uppercase header_size" style="margin-top: 6px !important;">@lang('vat::lang.vat_invoice')</h2>
    </div>

    {{-- Top customer/info box --}}
    <table style="width:100%; border-collapse: collapse; margin-bottom: 0;">
        <tr>
            {{-- Left: customer details --}}
            <td style="width: 55%; padding: 6px 8px; vertical-align: top;">
                <h5 class="customer_size" style="line-height: 1.4;"><strong>@lang('vat::lang.customer'):</strong> {{ strtoupper($receipt_details->customer_name) }}</h5>
                <h5 class="customer_size" style="line-height: 1.4;"><strong>@lang('vat::lang.address'):</strong> {{ $receipt_details->customer->address }}</h5>
                <h5 class="vat_size" style="line-height: 1.4;"><strong>@lang('contact.vat_number'):</strong> {{ $receipt_details->customer->vat_number }}</h5>
            </td>
            {{-- Middle blank spacer --}}
            <td style="width: 5%;">&nbsp;</td>
            {{-- Right: VAT registration / invoice / date each on 1 line --}}
            <td style="width: 40%; padding: 6px 8px; vertical-align: top;">
                <h5 class="registration_size" style="line-height: 1.4; white-space: nowrap;"><strong>@lang('vat::lang.vat_registration_no'):</strong> {{ $receipt_details->tax_info1 }}</h5>
                <h5 class="invoice_size" style="line-height: 1.4; white-space: nowrap;"><strong>@lang('vat::lang.invoice') #:</strong> {{ $receipt_details->invoice_no }}</h5>
                <h5 class="date_size" style="line-height: 1.4; white-space: nowrap;"><strong>@lang('vat::lang.invoice_date'):</strong> {{ $receipt_details->invoice_date ? \Carbon\Carbon::parse($receipt_details->invoice_date)->format('d.m.Y H:i') : '-' }}</h5>
            </td>
        </tr>
    </table>

    <h5 class="method_size" style="line-height: 1.4; padding-left: 8px;">
        <strong>@lang('vat::lang.payment_method'):</strong>
        @foreach ($payment_details as $pmt)
            {{ ucfirst(str_replace('_', ' ', $pmt->method)) }}@if (!$loop->last),@endif
        @endforeach
    </h5>

    <div class="row">
        <div class="col-xs-12">
            {{-- Main table --}}
            <table style="width: 100%; margin-top: 35px; border-collapse: collapse;"
                class="table">
                <tr style="background-color: #f5f5f5;">
                    <td class="thead_size uppercase text-center text-bold"
                        style="width: 12%; padding: 6px 4px; border: 1px solid #ddd;">Date</td>
                    <td class="thead_size uppercase text-center text-bold"
                        style="width: 33%; padding: 6px 4px; border: 1px solid #ddd; border-left: none;">Description</td>
                    @unless ($is_service_invoice)
                        <td class="thead_size uppercase text-center text-bold"
                            style="width: 10%; padding: 6px 4px; border: 1px solid #ddd; border-left: none;">QTY</td>
                        <td class="thead_size uppercase text-center text-bold"
                            style="width: 15%; padding: 6px 4px; border: 1px solid #ddd; border-left: none;">SALE INC. TAX</td>
                    @endunless
                    <td class="thead_size uppercase text-center text-bold"
                        style="width: 20%; padding: 6px 4px; border: 1px solid #ddd; border-left: none;">Amount</td>
                </tr>
                @php $i=0; @endphp
                @forelse($bill_details as $line)
                    @php $i++; @endphp
                    <tr>
                        <td class="tbody_size text-center" style="padding: 6px 4px; border: 1px solid #ddd; border-top: none;">
                            {{ $line->created_at ? \Carbon\Carbon::parse($line->created_at)->format('d.m.Y') : '-' }}
                        </td>
                        <td class="tbody_size" style="padding: 6px 4px; word-break: break-all; border: 1px solid #ddd; border-left: none; border-top: none;">
                            {{ strtoupper($line->product_name) }}
                        </td>
                        @unless ($is_service_invoice)
                            <td class="tbody_size text-right" style="padding: 6px 4px; border: 1px solid #ddd; border-left: none; border-top: none; text-align: right;">
                                {{ $formatVatQty($line->qty) }}</td>
                            <td class="tbody_size text-right" style="padding: 6px 4px; border: 1px solid #ddd; border-left: none; border-top: none; text-align: right;">
                                {{ @num_format($line->unit_price) }}</td>
                        @endunless
                        <td class="tbody_size text-right" style="padding: 8px 5px; border: 1px solid #ddd; border-left: none; border-top: none; text-align: right;">
                            {{ $formatVatSubTotal($line->sub_total) }}</td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="{{ $is_service_invoice ? 3 : 5 }}" style="padding: 6px 4px;">&nbsp;</td>
                    </tr>
                @endforelse

                {{-- Totals block: connected at the bottom-right of the same table, with only two right-hand columns (label + amount) and a light table structure --}}
                <tr>
                    <td colspan="{{ $is_service_invoice ? 1 : 3 }}">&nbsp;</td>
                    <td class="sub_size text-left"
                        style="padding: 8px 10px; background-color: #f5f5f5; border: 1px solid #ddd;">
                        <strong>@lang('vat::lang.total_invoice_amount_with_vat')</strong>
                    </td>
                    <td class="sub_size text-right"
                        style="padding: 8px 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-left: none; text-align: right;">
                        <strong>{{ $formatVatSubTotal($issue_customer_bill->total_amount) }}</strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="{{ $is_service_invoice ? 1 : 3 }}">&nbsp;</td>
                    <td class="sub_size text-left" style="padding: 8px 10px; border: 1px solid #ddd;">
                        @lang('vat::lang.tax_base_value')
                    </td>
                    <td class="sub_size text-right" style="padding: 8px 10px; border: 1px solid #ddd; border-left: none; text-align: right;">
                        {{ $formatVatSubTotal((float) $issue_customer_bill->total_amount - (float) $issue_customer_bill->tax_amount) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="{{ $is_service_invoice ? 1 : 3 }}">&nbsp;</td>
                    <td class="sub_size text-left" style="padding: 8px 10px; border: 1px solid #ddd;">
                        @lang('vat::lang.vat')
                        @if (!empty($receipt_details->tax_rate))
                            ({{ $receipt_details->tax_rate->amount }}%)
                        @endif
                    </td>
                    <td class="sub_size text-right" style="padding: 8px 10px; border: 1px solid #ddd; border-left: none; text-align: right;">
                        {{ $formatVatSubTotal($issue_customer_bill->tax_amount) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="{{ $is_service_invoice ? 1 : 3 }}">&nbsp;</td>
                    <td class="sub_size text-left" style="padding: 8px 10px; border: 1px solid #ddd;">
                        @lang('vat::lang.price_adjustment')
                    </td>
                    <td class="sub_size text-right" style="padding: 8px 10px; border: 1px solid #ddd; border-left: none; text-align: right;">
                        {{ $formatVatSubTotal($issue_customer_bill->price_adjustment ?? 0) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="{{ $is_service_invoice ? 1 : 3 }}">&nbsp;</td>
                    <td class="sub_size text-left"
                        style="padding: 8px 10px; background-color: #f5f5f5; border: 1px solid #ddd;">
                        <strong>@lang('vat::lang.total_invoice_amount_with_vat')</strong>
                    </td>
                    <td class="sub_size text-right"
                        style="padding: 8px 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-left: none; text-align: right;">
                        <strong>{{ $formatVatSubTotal((float) $issue_customer_bill->total_amount + (float) ($issue_customer_bill->price_adjustment ?? 0)) }}</strong>
                    </td>
                </tr>

            </table>

        </div>
    </div>

    <div style="height: 20px;"></div>

    {{-- Signatures line in a single row --}}
    <div class="row" style="margin-top: 10px;">
        <div class="footer_size col-xs-12 text-center">
            <table style="width:100%; border-collapse: collapse;">
                <tr>
                    <td style="width:33%; padding:0 30px; text-align:center;">
                        <div style="height: 30px;"></div>
                        <hr class="separator">
                        <h5 style="margin-top: 5px;">
                            <strong>@lang('vat::lang.prepared_by')</strong>
                        </h5>
                    </td>
                    <td style="width:33%; padding:0 30px; text-align:center;">
                        <div style="height: 30px;"></div>
                        <hr class="separator">
                        <h5 style="margin-top: 5px;">
                            <strong>@lang('vat::lang.checked_by')</strong>
                        </h5>
                    </td>
                    <td style="width:33%; padding:0 30px; text-align:center;">
                        <div style="height: 30px;"></div>
                        <hr class="separator">
                        <h5 style="margin-top: 5px;">
                            <strong>@lang('vat::lang.customer_signature')</strong>
                        </h5>
                    </td>
                </tr>
            </table>
        </div>
    </div>


    @if (!empty($admin_invoice_footer))
        <div style="position: fixed; bottom: 20px; left: 0; right: 0; text-align: center;">
            <p class="centered"
                style="font-size: {{ $f_font_size }}px !important;">
                {!! $admin_invoice_footer !!}
            </p>
        </div>
    @endif
</div>

<script>
    // Automatically open the browser print dialog when this page loads
    window.onload = function () {
        window.print();
    };

    // After printing (or cancelling), navigate back to the previous page
    window.onafterprint = function () {
        if (window.history.length > 1) {
            window.history.back();
        }
    };
</script>
