@extends('layouts.app')
@section('title', __('mpcs::lang.F25_form'))

@section('content')
<section class="content-header">
    <h1>@lang('mpcs::lang.F25_form') - {{ $header->form_no }}</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('messages.view')</h3>
            <div class="box-tools pull-right">
                <a href="{{ action('\Modules\MPCS\Http\Controllers\F25FormController@preview', ['id' => $header->id]) }}" target="_blank" class="btn btn-sm btn-info">
                    <i class="fa fa-search"></i> @lang('lang_v1.preview')
                </a>
                <a href="{{ action('\Modules\MPCS\Http\Controllers\F25FormController@print', ['id' => $header->id]) }}" target="_blank" class="btn btn-sm btn-primary">
                    <i class="fa fa-print"></i> @lang('mpcs::lang.print')
                </a>
                <a href="{{ action('\Modules\MPCS\Http\Controllers\F25FormController@pdf', ['id' => $header->id]) }}" target="_blank" class="btn btn-sm btn-warning">
                    <i class="fa fa-file-pdf-o"></i> PDF
                </a>
            </div>
        </div>
        <div class="box-body">
            <div style="margin-bottom: 25px; position: relative; padding: 15px; background-color: #fafafa; border-radius: 6px; border: 1px solid #eee;">
                <!-- Top Right F 25 title -->
                <div style="position: absolute; right: 20px; top: 15px; font-size: 24px; font-weight: 900; color: #333;">F 25</div>
                
                <!-- Top Center Business Location & title -->
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="font-size: 15px; font-weight: bold; color: #555;">Business Location: <span style="color: #222;">{{ $header->location_name }}</span></div>
                    <h3 style="margin: 5px 0 0 0; font-weight: bold; font-family: sans-serif; letter-spacing: 0.5px; color: #222; font-size: 20px;">Goods Issued Form</h3>
                </div>

                <!-- 2nd row details like Date, Supplier, Bill No, Delivery Location, Form No -->
                <div class="row" style="font-size: 13px;">
                    <div class="col-md-3">
                        <strong>Date:</strong> {{ @format_date($header->transaction_date) }}
                    </div>
                    <div class="col-md-3">
                        <strong>Supplier:</strong> {{ $header->supplier_name }}
                    </div>
                    <div class="col-md-2">
                        <strong>Bill No:</strong> {{ $header->bill_no }}
                    </div>
                    <div class="col-md-2">
                        <strong>Delivery Location:</strong> 
                        {{ !empty($header->delivery_code) ? str_pad((string) $header->delivery_code, 4, '0', STR_PAD_LEFT) : '' }} {{ !empty($header->delivery_location_name) ? '- '.$header->delivery_location_name : '' }}
                    </div>
                    <div class="col-md-2 text-right">
                        <strong>Form No:</strong> <span style="font-weight: bold; color: #3c8dbc;">{{ $header->form_no }}</span>
                    </div>
                </div>
            </div>

            <hr>

            <div class="table-responsive">
                <table class="table table-bordered table-condensed">
                    <thead style="background-color: #3c8dbc !important; color: #ffffff !important;">
                        <tr style="background-color: #3c8dbc !important; color: #ffffff !important;">
                            <th rowspan="2" class="text-center" style="vertical-align: middle; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">No</th>
                            <th rowspan="2" class="text-center" style="vertical-align: middle; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.bill_no')</th>
                            <th rowspan="2" class="text-center" style="vertical-align: middle; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.description_products')</th>
                            <th rowspan="2" class="text-center" style="vertical-align: middle; text-align: right; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.pcs')</th>
                            <th rowspan="2" class="text-center" style="vertical-align: middle; text-align: right; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.qty')</th>
                            <th colspan="2" class="text-center" style="vertical-align: middle; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.purchase_price')</th>
                            <th rowspan="2" class="text-center" style="vertical-align: middle; text-align: right; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.received_qty')</th>
                            <th colspan="2" class="text-center" style="vertical-align: middle; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.difference')</th>
                            <th colspan="2" class="text-center" style="vertical-align: middle; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.difference_in_cost')</th>
                            <th rowspan="2" class="text-center" style="vertical-align: middle; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.short_signature')</th>
                        </tr>
                        <tr style="background-color: #3c8dbc !important; color: #ffffff !important;">
                            <th class="text-center" style="text-align: right; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.unit_price')</th>
                            <th class="text-center" style="text-align: right; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.total')</th>
                            <th class="text-center" style="text-align: right; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.short')</th>
                            <th class="text-center" style="text-align: right; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.excess')</th>
                            <th class="text-center" style="text-align: right; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.short')</th>
                            <th class="text-center" style="text-align: right; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.excess')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($details as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $row->bill_no }}</td>
                                <td>{{ $row->description }}</td>
                                <td class="text-right">{{ $row->pcs }}</td>
                                <td class="text-right">{{ number_format((float) $row->qty, (int) ($quantity_precision ?? 2)) }}</td>
                                <td class="text-right">{{ number_format((float) $row->unit_price, (int) ($currency_precision ?? 2)) }}</td>
                                <td class="text-right">{{ number_format((float) $row->total_amount, (int) ($currency_precision ?? 2)) }}</td>
                                <td class="text-right">{{ number_format((float) $row->received_qty, (int) ($quantity_precision ?? 2)) }}</td>
                                <td class="text-right">{{ number_format((float) $row->short_qty, (int) ($quantity_precision ?? 2)) }}</td>
                                <td class="text-right">{{ number_format((float) $row->excess_qty, (int) ($quantity_precision ?? 2)) }}</td>
                                <td class="text-right">{{ number_format((float) $row->short_amount, (int) ($currency_precision ?? 2)) }}</td>
                                <td class="text-right">{{ number_format((float) $row->excess_amount, (int) ($currency_precision ?? 2)) }}</td>
                                <td>{{ $row->short_signature }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @php
                $first_detail = $details->first();
                /*
         * IS2122: the Received Date printed blank while the Time printed fine.
         *
         * The date was formatted with session('business.date_format'). In the
         * print and PDF contexts that session value is not always set, and
         * Carbon::format(null) returns an EMPTY STRING - so the date vanished
         * while the time, which is not formatted at all, still showed its raw
         * value. That is exactly the reported output: an empty Date and a bare
         * 09:07:00 sitting under it.
         *
         * A fallback format is used when the session has none. The time is
         * normalised to H:i as well, so 09:07:00 reads as 09:07.
         */
        $f25DateFormat = session('business.date_format') ?: 'd/m/Y';
        $received_date = !empty($first_detail->line_date)
            ? \Carbon\Carbon::parse($first_detail->line_date)->format($f25DateFormat)
            : '';
                $received_time = !empty($first_detail->line_time)
            ? \Carbon\Carbon::parse($first_detail->line_time)->format('H:i')
            : '';
                $field_21 = $first_detail->field_21 ?? '';
                $field_22 = $first_detail->field_22 ?? '';
            @endphp

            <div style="margin-top: 40px; background-color: #fafafa; padding: 25px; border-radius: 6px; border: 1px solid #e3e3e3; page-break-inside: avoid;">
                <table style="width: 100%; border: none; border-collapse: collapse; background-color: transparent;">
                    <tr style="border: none;">
                        <!-- Left Side: Received Header -->
                        <td style="width: 40%; border: none; padding: 4px 0; vertical-align: top;">
                            <h4 style="margin: 0; color: #222; font-weight: bold; font-family: sans-serif;">Received the above delivered Goods.</h4>
                        </td>
                        <!-- Driver Signature -->
                        <td style="width: 20%; border: none; padding: 4px 0; text-align: center; vertical-align: bottom;">
                            <div style="border-bottom: 1px dotted #555; margin: 0 10px 5px 10px; height: 35px;"></div>
                            <strong style="color: #444; font-size: 13px;">Driver</strong>
                        </td>
                        <!-- Officer Dispatch Signature -->
                        <td style="width: 20%; border: none; padding: 4px 0; text-align: center; vertical-align: bottom;">
                            <div style="border-bottom: 1px dotted #555; margin: 0 10px 5px 10px; font-weight: bold; min-height: 18px; color: #222; font-size: 14px;">
                                {{ $field_21 }}
                            </div>
                            <strong style="color: #444; font-size: 13px;">Officer (Dispatch)</strong>
                        </td>
                        <!-- Store Keeper Signature -->
                        <td style="width: 20%; border: none; padding: 4px 0; text-align: center; vertical-align: bottom;">
                            <div style="border-bottom: 1px dotted #555; margin: 0 10px 5px 10px; font-weight: bold; min-height: 18px; color: #222; font-size: 14px;">
                                {{ $field_22 }}
                            </div>
                            <strong style="color: #444; font-size: 13px;">Store Keeper</strong>
                        </td>
                    </tr>
                    <tr style="border: none;">
                        <!-- Left Side: Received Date & Time -->
                        <td style="width: 40%; border: none; padding: 25px 0 4px 0; vertical-align: top; font-size: 13px; color: #333;">
                            <span style="font-size: 14px;">
                                <strong>Received &nbsp;&nbsp;&nbsp;&nbsp; Date</strong> &nbsp;&nbsp; <span style="border-bottom: 1px dotted #555; padding: 0 15px; font-weight: bold; color: #111;">{{ $received_date }}</span>
                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                <strong>Time</strong> &nbsp;&nbsp; <span style="border-bottom: 1px dotted #555; padding: 0 15px; font-weight: bold; color: #111;">{{ $received_time }}</span>
                            </span>
                        </td>
                        <td style="width: 20%; border: none; padding: 25px 0 4px 0;"></td>
                        <td style="width: 20%; border: none; padding: 25px 0 4px 0;"></td>
                        <td style="width: 20%; border: none; padding: 25px 0 4px 0;"></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
