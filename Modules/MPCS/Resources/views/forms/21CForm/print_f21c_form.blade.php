<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>F21C-{{$details['F21c_from_no']}} {{request()->session()->get('business.name')}}</title>
    {{-- @include('layouts.partials.css') --}}
<style>
    @page {
        size: A3 landscape;
        margin: 8mm;
    }

    html, body {
        margin: 0;
        padding: 0;
        background: #fff !important;
        color: #000 !important;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 28px;
        line-height: 1.2;
    }

    body, body *,
    body *::before,
    body *::after {
        color: #000 !important;
        -webkit-text-fill-color: #000 !important;
        text-shadow: none !important;
    }

    table {
        border-collapse: collapse;
        color: #000 !important;
    }

    table tbody td,
    table thead th {
        border: 1px solid #000;
    }

    /* IS2337: 100% increase from the previous 14px print size. */
    #form_21c_table {
        width: 100% !important;
        table-layout: fixed;
    }

    #form_21c_table th,
    #form_21c_table td {
        font-size: 28px !important;
        line-height: 1.2 !important;
        color: #000 !important;
        -webkit-text-fill-color: #000 !important;
        background: #fff !important;
        padding: 8px !important;
        white-space: normal !important;
        overflow-wrap: anywhere;
        word-break: normal;
    }

    #form_21c_table thead th {
        font-weight: 700;
    }

    #form_21c_table th:first-child,
    #form_21c_table tbody td:first-child {
        width: 16%;
        min-width: 0;
        white-space: normal !important;
    }

    @media print {
        html, body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #form_21c_table {
            width: 100%;
            border-collapse: collapse;
        }

        #form_21c_table td,
        #form_21c_table th {
            padding: 8px;
            border: 1px solid #000;
            color: #000 !important;
            background: #fff !important;
        }

        #form_21c_table tr {
            margin-bottom: 4px;
        }

        #form_21c_table tbody td:first-child {
            white-space: normal !important;
            min-width: 0;
        }
    }
</style>
</head>
<body>
<table  style="width: 100%; border: none !important">
    <!-- Business Name -->
    <tr>
        <td colspan="4" class="text-center fw-bold" align="center">
            <h4>{{ request()->session()->get('business.name') }}</h4>
        </td>
    </tr>

    <!-- Form Title -->
    <tr>
        <td colspan="4" class="text-end" align="right">
            <h3 class="fw-bold">21C</h3>
        </td>
    </tr>

    <!-- Manager Name, Date, Balance Stock, Form Number -->
    <tr>
        <td class="fw-bold text-danger">Manager Name: {{ $details['manager_name'] ?? '' }}</td>
        <td class="fw-bold">Filling Station Date: {{ $details['form_21c_date'] ?? '' }}</td>
        <td class="fw-bold">Balance Stock For The Day:</td>
        <td class="fw-bold text-danger">Form No: {{ $details['F21c_from_no'] ?? '' }}</td>
    </tr>
</table><br>

        @php
        $index = 1;
        use Illuminate\Support\Arr;

        $excludedData = Arr::except($details, ['_token', '16a_location_id', 'form_16a_date', 'F21c_from_no', 'manager_name', 'form_21c_date']);


        $columnsArray = array(
            'receipts' => 'Receipts',
            'today' => 'Today',
            'previous_day' => 'Previous Day',
            'total_receipts' => 'Total Receipts',
            'f16_total_purchase' => 'Total Purchase Amount',
            'price_increment' => 'Price Increment',
            'price_inc_today' => 'Today',
            'price_inc_previous_day' => 'Previous Day',
            'price_inc_total' => 'Total Price Increment',
            'opening_stock' => 'Opening Stock',
            'total_receipts_today' => 'Total Receipts Today', 
            'issue' => 'Issue',
            'cash_for_today' => 'Cash for Today',
            'credit_for_today' => 'Credit for Today',
            'cooperative_section_for_today' => 'Cooperative Section for Today',
            'total_issues' => 'Total Issues',
            'issues_up_to_last_day' => 'Issues up to Last Day',
            'total_issues_one' => 'Total Issues (1)',
            'price_reduction' => 'Price Reduction',
            'price_discounts_for_today' => 'Price Reduction Today',
            'pre_date' => 'Price Reduction Previous Date',
            'total_discounts' => 'Total Discounts (2)',
            'total_for_today_one_plus_two' => 'Total for Today (1 + 2)',
            'balances' => 'Balances',
            'pump_meters' => 'Pump Meters'
            );
            
       @endphp

       <table class="table table-bordered" id="form_21c_table" style="width: 100%;" cellpadding="2" cellspacing="2">
    <thead>
        <tr>
            <th rowspan="3">Description</th>
            <th rowspan="3">No</th>
            @foreach ($fuelCategory as $categoryName)
                <th colspan="3">{{ $categoryName }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach ($fuelCategory as $categoryName)
                <th colspan="3">Tank Capacity</th>
            @endforeach
        </tr>
        <tr>
            @foreach ($fuelCategory as $categoryName)
                <th>Balance Qty</th>
                <th colspan="2">Value</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($columnsArray as $colKey => $label)
        @php
            $column = $excludedData[$colKey] ?? null;
            $isHeader = in_array($colKey, ['receipts', 'issue', 'price_increment', 'price_reduction', 'pump_meters']);
        @endphp
        @if ($column || $isHeader)
        <tr>
            <td style="{{ $isHeader ? 'font-weight: bold;' : '' }}">{{ $label }}</td>
            @if ($isHeader)
                <td colspan="{{ count($fuelCategory) * 3 + 1 }}"></td>
            @else
                <td>{{ $column['no'] ?? '' }}</td>
                @foreach ($fuelCategory as $categoryKey => $categoryName)
                    <td>{{ $column[$categoryKey]['qty'] ?? '' }}</td>
                    <td>{{ $column[$categoryKey]['val'] ?? '' }}</td>
                    <td>{{ $column[$categoryKey]['dec'] ?? '' }}</td>
                @endforeach
            @endif
        </tr>
        @endif
        @endforeach
    </tbody>
    <tfoot class="bg-gray">
                    <tr>
                        <td colspan="11"> That all the details are entered correctly</td>
                    </tr>
                    <tr>
                        <td colspan="7"  class="text-left" style="border: 0px !important">
                            <h5 style="font-weight: bold; margin-bottom: 0px; ">
                                @lang('mpcs::lang.checked_by'): ____________</h5>
                        </td>
                        <td colspan="4" style="border: 0px !important">
                            <h5 style="font-weight: bold; margin-bottom: 0px; ">
                            @lang('mpcs::lang.signature_of_manager'): ____________</h5> <br>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="7" class="text-left" style="border: 0px !important">
                            <h5 style="font-weight: bold; margin-bottom: 0px; ">
                                @lang('mpcs::lang.last_date'): ____________</h5>
                        </td>
                        <td colspan="4" style="border: 0px !important">
                            <h5 style="font-weight: bold; margin-bottom: 0px; ">
                                @lang('mpcs::lang.date'): ____________</h5>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="7"  class="text-left" style="border: 0px !important">
                            <h5 style="font-weight: bold; margin-top: 10px; ">@lang('mpcs::lang.user'):
                                {{auth()->user()->username }}</h5>
                        </td>
                        <td colspan="4" style="border: 0px !important">
                            <h5 style="font-weight: bold; margin-bottom: 0px; ">
                                </h5>
                        </td>
                    </tr>
                </tfoot>
</table>
           
       
    </div>


    {{-- @include('layouts.partials.javascripts') --}}
 
</body>

</html>
