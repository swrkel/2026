<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mpcs::lang.F25_form') }} - {{ $header->form_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 18px; }
        .header-table, .line-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .header-table td { padding: 4px 6px; border: 1px solid #ddd; }
        .line-table th, .line-table td { border: 1px solid #bbb; padding: 4px 6px; }
        .line-table th { background: #f4f4f4; }
        .text-right { text-align: right; }
        h3 { margin: 0 0 10px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    @if(empty($is_pdf))
        <div class="no-print" style="margin-bottom:10px;">
            <button onclick="window.print()">{{ __('mpcs::lang.print') }}</button>
        </div>
    @endif

    <div style="margin-bottom: 20px; position: relative;">
        <!-- Top Right F 25 title -->
        <div style="position: absolute; right: 0; top: 0; font-size: 20px; font-weight: bold; font-family: sans-serif;">F 25</div>
        
        <!-- Top Center Business Location & title -->
        <div style="text-align: center; margin-bottom: 15px;">
            <div style="font-size: 14px; font-weight: bold;">Business Location: {{ $header->location_name }}</div>
            <h2 style="margin: 5px 0 0 0; font-weight: bold; font-family: sans-serif; letter-spacing: 0.5px;">Goods Issued Form</h2>
        </div>

        <!-- 2nd row details like Date, Supplier, Bill No, Delivery Location, Form No -->
        <table style="width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 15px;">
            <tr>
                <td style="width: 20%; padding: 5px; border: 1px solid #ccc;">
                    <strong>Date:</strong> {{ @format_date($header->transaction_date) }}
                </td>
                <td style="width: 25%; padding: 5px; border: 1px solid #ccc;">
                    <strong>Supplier:</strong> {{ $header->supplier_name }}
                </td>
                <td style="width: 15%; padding: 5px; border: 1px solid #ccc;">
                    <strong>Bill No:</strong> {{ $header->bill_no }}
                </td>
                <td style="width: 25%; padding: 5px; border: 1px solid #ccc;">
                    <strong>Delivery Location:</strong> 
                    {{ !empty($header->delivery_code) ? str_pad((string) $header->delivery_code, 4, '0', STR_PAD_LEFT) : '' }} {{ !empty($header->delivery_location_name) ? '- '.$header->delivery_location_name : '' }}
                </td>
                <td style="width: 15%; padding: 5px; border: 1px solid #ccc;">
                    <strong>Form No:</strong> {{ $header->form_no }}
                </td>
            </tr>
        </table>
    </div>

    <table class="line-table">
        <thead>
            <tr>
                <th rowspan="2" style="vertical-align: middle; text-align: center;">No</th>
                <th rowspan="2" style="vertical-align: middle; text-align: center;">{{ __('mpcs::lang.bill_no') }}</th>
                <th rowspan="2" style="vertical-align: middle; text-align: center;">{{ __('mpcs::lang.description_products') }}</th>
                <th rowspan="2" class="text-right" style="vertical-align: middle;">{{ __('mpcs::lang.pcs') }}</th>
                <th rowspan="2" class="text-right" style="vertical-align: middle;">{{ __('mpcs::lang.qty') }}</th>
                <th colspan="2" style="text-align: center;">{{ __('mpcs::lang.purchase_price') }}</th>
                <th rowspan="2" class="text-right" style="vertical-align: middle;">{{ __('mpcs::lang.received_qty') }}</th>
                <th colspan="2" style="text-align: center;">{{ __('mpcs::lang.difference') }}</th>
                <th colspan="2" style="text-align: center;">{{ __('mpcs::lang.difference_in_cost') }}</th>
                <th rowspan="2" style="vertical-align: middle; text-align: center;">{{ __('mpcs::lang.short_signature') }}</th>
            </tr>
            <tr>
                <th class="text-right">{{ __('mpcs::lang.unit_price') }}</th>
                <th class="text-right">{{ __('mpcs::lang.total') }}</th>
                <th class="text-right">{{ __('mpcs::lang.short') }}</th>
                <th class="text-right">{{ __('mpcs::lang.excess') }}</th>
                <th class="text-right">{{ __('mpcs::lang.short') }}</th>
                <th class="text-right">{{ __('mpcs::lang.excess') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row->bill_no }}</td>
                    <td>{{ $row->description }}</td>
                    <td class="text-right">
                        @php
                            $pcsRaw = (string) ($row->pcs ?? '');
                            $pcsNumeric = str_replace(',', '', trim($pcsRaw));
                        @endphp
                        @if($pcsRaw !== '' && preg_match('/^-?\d+(\.\d+)?$/', $pcsNumeric))
                            {{ number_format((float) $pcsNumeric, (int) ($quantity_precision ?? 2)) }}
                        @else
                            {{ $row->pcs }}
                        @endif
                    </td>
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

    <div style="margin-top: 40px; page-break-inside: avoid; font-family: sans-serif; font-size: 12px;">
        <table style="width: 100%; border: none; border-collapse: collapse; background-color: transparent;">
            <tr style="border: none;">
                <!-- Left Side: Received Header -->
                <td style="width: 40%; border: none; padding: 4px 0; vertical-align: top;">
                    <strong style="font-size: 13px; color: #111;">Received the above delivered Goods.</strong>
                </td>
                <!-- Driver Signature -->
                <td style="width: 20%; border: none; padding: 4px 0; text-align: center; vertical-align: bottom;">
                    <div style="border-bottom: 1px dotted #333; margin: 0 10px 5px 10px; height: 35px;"></div>
                    <strong>Driver</strong>
                </td>
                <!-- Officer Dispatch Signature -->
                <td style="width: 20%; border: none; padding: 4px 0; text-align: center; vertical-align: bottom;">
                    <div style="border-bottom: 1px dotted #333; margin: 0 10px 5px 10px; font-weight: bold; min-height: 18px; color: #111;">
                        {{ $field_21 }}
                    </div>
                    <strong>Officer (Dispatch)</strong>
                </td>
                <!-- Store Keeper Signature -->
                <td style="width: 20%; border: none; padding: 4px 0; text-align: center; vertical-align: bottom;">
                    <div style="border-bottom: 1px dotted #333; margin: 0 10px 5px 10px; font-weight: bold; min-height: 18px; color: #111;">
                        {{ $field_22 }}
                    </div>
                    <strong>Store Keeper</strong>
                </td>
            </tr>
            <tr style="border: none;">
                <!-- Left Side: Received Date & Time -->
                <td style="width: 40%; border: none; padding: 25px 0 4px 0; vertical-align: top;">
                    <span style="font-size: 12px; color: #222;">
                        <strong>Received &nbsp;&nbsp;&nbsp;&nbsp; Date</strong> &nbsp;&nbsp; <span style="border-bottom: 1px dotted #333; padding: 0 15px; font-weight: bold;">{{ $received_date }}</span>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <strong>Time</strong> &nbsp;&nbsp; <span style="border-bottom: 1px dotted #333; padding: 0 15px; font-weight: bold;">{{ $received_time }}</span>
                    </span>
                </td>
                <td style="width: 20%; border: none; padding: 25px 0 4px 0;"></td>
                <td style="width: 20%; border: none; padding: 25px 0 4px 0;"></td>
                <td style="width: 20%; border: none; padding: 25px 0 4px 0;"></td>
            </tr>
        </table>
    </div>
</body>
@if(!empty($is_preview) && empty($is_pdf))
    <script>
        // Preview page should not auto-print.
    </script>
@elseif(empty($is_pdf) && isset($is_preview) && $is_preview === false)
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
@endif
</html>
