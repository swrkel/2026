<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title></title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            page-break-inside: auto;
        }

        table th,
        table td {
            border: 1px solid black;
            font-size: 13px;
            padding: 4px;
        }

        /* ✅ Right-align for all quantity and amount fields */
        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .page {
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: avoid;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .footer {
            margin-top: 10px;
        }

        .text-bold {
            font-weight: bold;
        }

        .text-red {
            color: red;
        }

        .bg-gray {
            background-color: #f2f2f2;
        }

        .pump-section table {
            display: block;
            overflow-x: auto;
        }

        .pumps-container {
            display: flex;
            gap: 10px;
            align-items: stretch;
        }

        .no-wrap {
            white-space: nowrap;
        }

        .column-50 {
            width: 25%;
        }

        .pump-section {
            flex: 1;
            border-right: 3px solid skyblue;
            padding-right: 10px;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
    </style>

</head>

<body>
    @php
        $index = 1;
        $chunk_number = !empty($settings->F22_no_of_product_per_page) ? $settings->F22_no_of_product_per_page : 25;
        $chuncks = array_chunk($data, $chunk_number);
        $pre_page_total_purchase = 0;
        $pre_page_total_sale = 0;
        $grand_page_total_purchase = 0;
        $grand_page_total_sale = 0;
        $f22Number = function ($value) {
            if ($value === null || $value === '') {
                return null;
            }

            return (float) str_replace(',', '', (string) $value);
        };
    @endphp

    @foreach ($chuncks as $key => $detail)
        <div class="page">
            <div class="header">
                <div>
                    <h5 style="font-weight: bold; margin-bottom: 5px;">
                        {{ request()->session()->get('business.name') }} - <span class="f22_location_name">{{ $details['f22_location_name'] }}</span>
                    </h5>
                </div>
                <div>
                    <h5 style="font-weight: bold; color: red; margin-top: 5px;">
                        @lang('mpcs::lang.f22_form_no') : {{ $details['F22_from_no'] }}
                    </h5>
                </div>
                <div>
                    <label for="date_range_filter" class="control-label">Date: 
                        @if(!empty($date) && $date != '0000-00-00')
                            {{ \Carbon\Carbon::parse($date)->format('Y-m-d') }}
                        @else
                            {{ now()->format('Y-m-d') }}
                        @endif
                    </label>
                </div>
            </div>

            @php
                $this_page_total_purchase = 0.0;
                $this_page_total_sale = 0.0;
            @endphp

            <table class="table table-bordered table-striped" id="form_22_table">
                <thead>
                    <tr>
                        <th>@lang('mpcs::lang.index_no')</th>
                        <th>@lang('mpcs::lang.code')</th>
                        <th>@lang('mpcs::lang.book_no')</th>
                        <th>@lang('mpcs::lang.product')</th>
                        <th>@lang('mpcs::lang.current_stock')</th>
                        <th>@lang('mpcs::lang.stock_count')</th>
                        <th>@lang('mpcs::lang.unit_purchase_price')</th>
                        <th>@lang('mpcs::lang.total_purchase_price')</th>
                        <th>@lang('mpcs::lang.unit_sale_price')</th>
                        <th>@lang('mpcs::lang.total_sale_price')</th>
                        <th>@lang('mpcs::lang.qty_difference')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detail as $item)
                        @php
                            $current_stock = $f22Number($item['current_stock'] ?? null);
                            $stock_count = $f22Number($item['stock_count'] ?? null);
                            $unit_purchase_price = $f22Number($item['unit_purchase_price'] ?? null);
                            $unit_sale_price = $f22Number($item['unit_sale_price'] ?? null);
                            $total_purchase_price = $f22Number($item['total_purchase_price'] ?? ($item['total_purhcase_value'] ?? null));
                            $total_sale_price = $f22Number($item['total_sale_price'] ?? ($item['total_sale_value'] ?? null));
                            $qty_difference = $f22Number($item['qty_difference'] ?? ($item['difference_qty'] ?? null));

                            if ($total_purchase_price === null && $stock_count !== null && $unit_purchase_price !== null) {
                                $total_purchase_price = $stock_count * $unit_purchase_price;
                            }

                            if ($total_sale_price === null && $stock_count !== null && $unit_sale_price !== null) {
                                $total_sale_price = $stock_count * $unit_sale_price;
                            }
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index }}</td>
                            <td class="text-center">{{ !empty($item['sku']) ? $item['sku'] : '' }}</td>
                            <td class="text-center">{{ !empty($item['book_no']) ? $item['book_no'] : '' }}</td>
                            <td class="text-left">{{ !empty($item['product']) ? $item['product'] : '' }}</td>

                            <!-- Right-aligned numeric fields -->
                            <td class="text-right">
                                {{ $current_stock !== null ? number_format($current_stock, 2) : '' }}
                            </td>
                            <td class="text-right">
                                {{ $stock_count !== null ? number_format($stock_count, 2) : '' }}
                            </td>
                            <td class="text-right">
                                {{ $unit_purchase_price !== null ? number_format($unit_purchase_price, 2) : '' }}
                            </td>
                            <td class="text-right">
                                {{ $total_purchase_price !== null ? number_format($total_purchase_price, 2) : '' }}
                            </td>
                            <td class="text-right">
                                {{ $unit_sale_price !== null ? number_format($unit_sale_price, 2) : '' }}
                            </td>
                            <td class="text-right">
                                {{ $total_sale_price !== null ? number_format($total_sale_price, 2) : '' }}
                            </td>
                            <td class="text-right">
                                {{ $qty_difference !== null ? number_format($qty_difference, 2) : '' }}
                            </td>
                        </tr>
                        @php
                            $index++;
                            $page_purchase = $total_purchase_price ?? 0;
                            $page_sale = $total_sale_price ?? 0;

                            $this_page_total_purchase += $page_purchase;
                            $this_page_total_sale += $page_sale;
                            $grand_page_total_purchase += $page_purchase;
                            $grand_page_total_sale += $page_sale;
                        @endphp
                    @endforeach
                </tbody>

                <tfoot class="bg-gray">
                    <tr>
                        <td class="text-red text-bold" colspan="7">@lang('mpcs::lang.total_this_page')</td>
                        <td class="text-right text-red text-bold" id="footer_total_purchase_price">
                            {{number_format($this_page_total_purchase, 2)}}</td>
                        <td>&nbsp;</td>
                        <td class="text-right text-red text-bold" colspan="2" id="footer_total_sale_price">
                            {{number_format($this_page_total_sale, 2)}}</td>
                    </tr>
                    <tr>
                        <td class="text-red text-bold" colspan="7">@lang('mpcs::lang.total_previous_page')</td>
                        <td class="text-right text-red text-bold" id="pre_total_purchase_price">
                            {{number_format($pre_page_total_purchase, 2)}}</td>
                        <td>&nbsp;</td>
                        <td class="text-right text-red text-bold" colspan="2" id="pre_total_sale_price">
                            {{number_format($pre_page_total_sale, 2)}}</td>
                    </tr>
                    <tr>
                        <td class="text-red text-bold" colspan="7">@lang('mpcs::lang.grand_total')</td>
                        <td class="text-right text-red text-bold" id="grand_total_purchase_price">
                            {{number_format($grand_page_total_purchase, 2)}}</td>
                        <td>&nbsp;</td>
                        <td class="text-right text-red text-bold" colspan="2" id="grand_total_sale_price">
                            {{number_format($grand_page_total_sale, 2)}}</td>
                    </tr>
                    @if ($loop->last)
                    <tr>
                        <td colspan="11">
                            <h3 style="color:red;">Pumps & Meters</h3>
                            <div class="pumps-container">
                                @foreach($pumps->groupBy('product_name') as $product => $groupedPumps)
                                    <div class="pump-section" style="width:100%;">
                                        <h4>{{ $product }}</h4>
                                        <table class="table table-bordered" style="border: none; width: 100%;">
                                            <tr>
                                                <th>Pump Name</th>
                                                <th>Current Meter</th>
                                            </tr>
                                            @foreach($groupedPumps as $pump)
                                                <tr>
                                                    <td>{{ $pump->pump_name }}</td>
                                                    <td>{{ $pump->last_meter_reading }}</td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="11"> @lang('mpcs::lang.confirm_f22')</td>
                    </tr>
                    <tr>
                        <td colspan="7" class="text-left" style="border: 0px !important">
                            @lang('mpcs::lang.checked_by'): ____________
                        </td>
                        <td colspan="4" style="border: 0px !important">
                            @lang('mpcs::lang.received_by'): ____________
                        </td>
                    </tr>
                    <tr>
                        <td colspan="7" class="text-left" style="border: 0px !important">
                            @lang('mpcs::lang.signature_of_manager'): ____________
                        </td>
                        <td colspan="4" style="border: 0px !important">
                            @lang('mpcs::lang.handed_over_by'): ____________
                        </td>
                    </tr>
                    <tr>
                        <td colspan="7" class="text-left" style="border: 0px !important">
                            @lang('mpcs::lang.user'): {{ auth()->user()->username }}
                        </td>
                        <td colspan="4" style="border: 0px !important">
                            &nbsp;
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @php
            $pre_page_total_purchase = $grand_page_total_purchase;
            $pre_page_total_sale = $grand_page_total_sale;
        @endphp
    @endforeach
</body>

</html>
