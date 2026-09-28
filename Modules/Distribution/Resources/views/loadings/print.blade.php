<!DOCTYPE html>
<html>

<head>
    <title>Loading Sheet {{ $loading->loading_no }}</title>

    <style>
        @page {
            margin: 20px;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        .header {
            text-align: center;
            font-weight: bold;
            font-size: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        th {
            background: #f2f2f2;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .totals-row td {
            font-weight: bold;
        }

        .signatures {
            margin-top: 40px;
            width: 100%;
        }

        .signature-box {
            width: 45%;
            display: inline-block;
            text-align: center;
        }

        .signature-line {
            margin-top: 40px;
            border-top: 1px dashed #000;
        }

        /* Footer on every page */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>

<body>

    <div class="header">
        {{ $business_location->name }}
    </div>

    <h3 style="text-align:center;">Product Loading Sheet</h3>

    <table style="border:none; margin-bottom:10px;">
        <tr style="border:none;">
            <td style="border:none;"><strong>Loading No:</strong> {{ $loading->loading_no }}</td>
            <td style="border:none;"><strong>Date:</strong>
                {{ \Carbon\Carbon::parse($loading->date_time)->format('Y-m-d') }}</td>
            <td style="border:none;"><strong>Sales Rep:</strong> {{ $loading->salesRep->name ?? '' }}</td>
            <td style="border:none;"><strong>Vehicle:</strong> {{ $loading->vehicle->vehicle_no ?? '' }}</td>
        </tr>
        <tr style="border:none;">
            <td style="border:none;"><strong>Category:</strong> {{ $loading->category->name ?? 'All' }}</td>
            <td style="border:none;"><strong>Subcategory:</strong> {{ $loading->subcategory->name ?? 'All' }}</td>
            <td style="border:none;"></td>
            <td style="border:none;"></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>@lang('distribution::lang.index')</th>
                <th>@lang('distribution::lang.product')</th>
                <th>@lang('distribution::lang.available_qty')</th>
                <th>@lang('distribution::lang.vehicle_balance_qty')</th>
                <th>@lang('distribution::lang.requested_qty')</th>
                <th>@lang('distribution::lang.issued_qty')</th>
                <th>@lang('distribution::lang.unit_sale_price')</th>
                <th>@lang('distribution::lang.total_in_sale_price')</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total_vehicle_balance = 0;
                $total_requested = 0;
                $total_issued = 0;
                $total_sale_price = 0;
            @endphp

            @foreach ($loading->lines as $i => $line)
                @php
                    $total_vehicle_balance += (float) $line->vehicle_balance_qty;
                    $total_requested += (float) $line->requested_qty;
                    $total_issued += (float) $line->issued_qty;
                    $total_sale_price += (float) $line->line_total;
                @endphp

                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="text-left">{{ $line->product->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format((float) $line->available_qty, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $line->vehicle_balance_qty, 2) }}
                    </td>
                    <td class="text-right">{{ number_format((float) $line->requested_qty, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $line->issued_qty, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $line->sale_price, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $line->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>

        <tfoot>
            <tr class="totals-row">
                <td></td>
                <td class="text-right">TOTAL</td>
                <td></td>
                <td class="text-right">{{ number_format((float) $total_vehicle_balance, 2) }}</td>
                <td class="text-right">{{ number_format((float)$total_requested, 2) }}</td>
                <td class="text-right">{{ number_format((float)$total_issued, 2) }}</td>
                <td></td>
                <td class="text-right">{{ number_format((float)$total_sale_price, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line"></div>
            Store Keeper Signature
        </div>

        <div class="signature-box" style="float:right;">
            <div class="signature-line"></div>
            Sales Rep Signature
        </div>
    </div>

    <div class="page-footer">
        {!! $report_footer !!}
    </div>


</body>

</html>
