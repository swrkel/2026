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
            ->first()->tax_report_name ?? 'tax';

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
        margin: 5px;
        padding: 70px 100px 30px 100px;
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
    .invoice_header{
        text-align: center;
    }
</style>
<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" type="text/css" rel="stylesheet">
<style>
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        margin: 0;
        font-family: Calibri, sans-serif !important;
        font-weight: 500;
    }
    p{
        font-family: Calibri, sans-serif !important;
        font-size: 15px;
        line-height: 26px;
        color: #444;
        margin-bottom: 0;
    }
    .table>tbody>tr>td, .table>tbody>tr>th, .table>tfoot>tr>td, .table>tfoot>tr>th, .table>thead>tr>td, .table>thead>tr>th{
        border: 1px solid #ddd;
    }
    .separator {
        border: 1px dotted black;
    }
</style>
<style>
    .h5, h5 {
        font-size: 17px;
    }
    .col-xs-6{
        padding-right: 0px;
    }
</style>
<div class="A4" style="width: 100% !important;position: relative;">
    <div class="row" style="display: flex; align-items: center; position: relative;">

        {{-- Logo --}}
        @if (!empty($vat_logo) && file_exists(public_path($vat_logo)))
            <div class="img" style="margin-right: 20px;">
                <div style="display: inline-block; border: 2px solid black; padding: 10px;">
                   <img
                        src="{{ asset($vat_logo) }}"
                        alt="VAT Logo"
                        style="
                            {{ !empty($vat_logo_width) ? 'width:' . $vat_logo_width . 'px;' : 'width:auto;' }}
                            {{ !empty($vat_logo_height) ? 'height:' . $vat_logo_height . 'px;' : 'height:auto;' }}
                        "
                    >
                </div>
            </div>
        @endif

        {{-- Invoice Header --}}
        <div class="col-xs-12 invoice_header" style="flex: 1; text-align: center;">
            <div style="display: inline-block; border: 2px solid black; padding: 10px;">
                <h2 class="uppercase header_size" style="margin:0; font-size:25px;">
                    @lang('vat::lang.tax') @lang('vat::lang.invoice')
                </h2>
            </div>
        </div>

        {{-- Duplicate --}}
        @if ($vat_statement_126_2026_print > 1)
            <div class="duplicate" style="margin-left: 20px;">
                <div style="display: inline-block; border: 2px solid black; padding: 10px;">
                    <span>Duplicate - {{ $vat_statement_126_2026_print }}</span>
                </div>
            </div>
        @endif

    </div>
    <div style="height: 20px;"></div>
    <div class="row" style="display: flex;justify-content: end;">
         <div class="col-xs-3 text-left" style="padding:10px;">
            <h5 class="customer_size" style="margin-bottom: 8px;">
                <strong>@lang('vat::lang.statment_vat_no'):</strong>
                <strong style="font-size: 14px;">{{ $receipt_details->invoice_no }}</strong></h5>
            <h5 class="customer_size" style="margin-bottom: 8px;">
                <strong>@lang('vat::lang.statment_invoice') #:</strong>
               <strong style="font-size: 14px;">SFS - {{ $statement->id }}</strong></h5>
            <h5 class="customer_size">
                <strong>@lang('vat::lang.invoice_date'):</strong>
                <strong style="font-size: 14px;">{{ $statement->date ? \Carbon\Carbon::parse($statement->date)->format('d/m/Y') : '-' }}</strong></h5>
        </div>
    </div>
    <div class="row" style="margin-bottom: 6px;">
        <div class="col-xs-6 text-left " style="padding-left: 0px;">
             <div class="col-12 text-left" style="margin-bottom: 8px;padding:10px;">
                <h5 class="customer_size" style="margin-bottom: 8px;">
                    <strong>@lang('vat::lang.customer_name'):</strong>
                    <strong style="font-size: 14px;">{{ strtoupper($receipt_details->customer_name) }}</strong></h5>
                <h5 class="customer_size" style="margin-bottom: 8px;">
                    <strong>@lang('vat::lang.address'):</strong>
                    <strong style="font-size: 14px;">{{  $receipt_details->customer->address ?? '-' }}</strong></h5>
                <h5 class="customer_size">
                <strong>@lang('vat::lang.statment_vat_no'):</strong>
                <strong style="font-size: 14px;">{{ $receipt_details->invoice_no ?? '-' }}</strong></h5>
            </div>

        </div>
    </div>

    <div class="row">
        <div class="col-xs-12" style="padding-left: 0px;padding-right:0px;">
              <table style="width: 100%; margin-top: 20px; border-collapse: collapse;"
                class="table">
                 <colgroup>
                    <col style="width:7%">
                    <col style="width:32%">
                    <col style="width:6%">
                    <col style="width:15%">
                    <col style="width:20%">
                </colgroup>

                <tr style="background-color:#f5f5f5;">
                    <th class="thead_size uppercase text-center text-bold" style="padding:10px 5px;line-height: 18px;font-size: 14px;">
                        @lang('vat::lang.date')
                    </th>
                    <th class="thead_size uppercase text-center text-bold" style="padding:10px 5px;line-height: 18px;font-size: 14px;">
                        @lang('vat::lang.description_of_goods_or_services')
                    </th>
                    <th class="thead_size uppercase text-center text-bold" style="padding:10px 5px;line-height: 18px;font-size: 14px;">
                        @lang('vat::lang.quantity')
                    </th>
                    <th class="thead_size uppercase text-center text-bold" style="padding:10px 5px;line-height: 18px;font-size: 14px;">
                        SALE INC. TAX
                    </th>
                    <th class="thead_size uppercase text-center text-bold" style="padding:10px 5px;line-height: 18px;font-size: 14px;">
                        @lang('vat::lang.amount')
                    </th>
                </tr>
                @php $i=0; @endphp
                @forelse($statement_details as $line)
                    @php $i++; @endphp
                    <tr>
                        <td class="tbody_size text-center bordered" style="padding: 8px 5px;font-size:13px;line-height: 18px;">
                           {{ $line->created_at ? \Carbon\Carbon::parse($line->created_at)->format('d.m.Y') : '-' }}
                        </td>
                        <td class="tbody_size bordered text-center" style="padding: 8px 5px; word-break: break-all;font-size:13px;line-height: 18px;">
                            {{ $line->product_name ?? '-' }}
                        </td>
                        <td class="tbody_size text-right bordered" style="padding: 8px 5px;line-height: 18px;">
                            {{ @num_format($line->qty) }}</td>
                        <td class="tbody_size text-right bordered" style="padding: 8px 5px;line-height: 18px;">
                            {{ @num_format($line->unit_price) }}</td>
                        <td class="tbody_size text-right bordered" style="padding: 8px 5px;line-height: 18px;">
                            @php
                                $unit_price_qty = $line->unit_price_before_tax * $line->qty;
                            @endphp
                            {{ @num_format($line->sub_total) }}</td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="8" style="padding: 8px 5px;">&nbsp;</td>
                    </tr>
                @endforelse
            </table>
        </div>
    </div>
    <div class="row receipt_details_total_row" style="display: flex;justify-content: end;">
        <div class="col-xs-4" style="padding-left: 0px;padding-right:0px;">
              <table style="width: 100%; border-collapse: collapse;"
                class="table">
                <tr>
                    <td class="sub_size text-left bordered"
                        style="padding: 8px 10px; background-color: #f5f5f5;line-height: 18px;font-size: 15px;"
                        colspan="4"><strong>@lang('vat::lang.total_without_vat')</strong></td>
                    <td class="sub_size text-right bordered"
                        style="padding: 8px 10px; background-color: #f5f5f5;line-height: 18px;font-size: 15px;">
                        <strong>{{ $receipt_details->total }}</strong></td>
                </tr>
                <tr>
                    
                    <td class="sub_size text-left bordered"
                        style="padding: 8px 10px; background-color: #f5f5f5;line-height: 18px;font-size: 15px;"
                        colspan="4"><strong>@lang('vat::lang.vat_18')</strong></td>
                    <td class="sub_size text-right bordered"
                        style="padding: 8px 10px;background-color: #f5f5f5;line-height: 18px;font-size: 15px;">
                        <strong>{{ $receipt_details->vat_rate }}</strong></td>
                </tr>
                <tr>
                    <td class="sub_size text-left bordered"
                        style="padding: 8px 10px; background-color: #f5f5f5;line-height: 18px;font-size: 15px;"
                        colspan="4"><strong>@lang('vat::lang.total')</strong></td>
                    <td class="sub_size text-right bordered"
                        style="padding: 8px 10px;background-color: #f5f5f5;line-height: 18px;font-size: 15px;">
                        <strong>{{ $receipt_details->total_with_vat }}</strong></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="row" style="margin-bottom:10px;">
        <div class="footer_size col-xs-12 text-center">

            <div class="col-xs-4 text-center" style="padding: 0 50px;">
                <div style="height: 60px;"></div>
                <hr class="separator">
                <h5 style="margin-top: 10px;"><strong>@lang('vat::lang.prepared_by')</strong></h5>
            </div>

            <div class="col-xs-4 text-center" style="padding: 0 50px;">
                <div style="height: 60px;"></div>
                <hr class="separator">
                <h5 style="margin-top: 10px;"><strong>@lang('vat::lang.checked_by')</strong></h5>
            </div>

            <div class="col-xs-4 text-center" style="padding: 0 50px;">
                <div style="height: 60px;"></div>
                <hr class="separator">
                <h5 style="margin-top: 10px;"><strong>@lang('vat::lang.customer_signature')</strong></h5>
            </div>

        </div>
    </div>

    @if (!empty($admin_invoice_footer))
        <div class="row">
            <div class="col-xs-12 text-center">
                <p class="centered"
                    style="font-size: {{ $f_font_size }}px !important; margin-top: @if (!empty($footer_top_margin)) {{ $footer_top_margin }}px; @else 10px; @endif">
                    {!! $admin_invoice_footer !!}
                </p>
            </div>
        </div>
    @endif
</div>
