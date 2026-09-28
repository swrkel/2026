<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        table th,
        table td {
            border: 1px solid black;
            padding: 4px;
        }

        @media print {
            .page-break {
                page-break-after: always;
            }

            .no-page-break {
                page-break-after: auto;
            }

            tfoot {
                page-break-inside: avoid;
            }
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
    <div class="col-md-12" style="text-align:center;">
        <div class="row">
            <div class="col-md-5">
                <div class="text-center">
                    <h5 style="font-weight: bold;">
                        {{ request()->session()->get('business.name') }} - <span class="f22_location_name">{{ $header->location_name }}</span>
                    </h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center pull-left">
                    <h5 style="font-weight: bold;" class="text-red">
                        @lang('mpcs::lang.f22_form_no') : {{ $header->form_no }}
                    </h5>
                </div>
            </div>
            <div class="col-md-4" style="float:right;">
                <div class="text-center pull-left">
                    <h5 style="font-weight: bold;" class="text-red">
                        @lang('mpcs::lang.date_and_time') : 
                        @if(!empty($header->form_date) && $header->form_date != '0000-00-00')
                            {{ \Carbon\Carbon::parse($header->form_date)->format('Y-m-d') }}
                        @else
                            {{ \Carbon\Carbon::parse($header->created_at)->format('Y-m-d') }}
                        @endif
                    </h5>
                </div>
            </div>
        </div>

        @php
            $index = 1;
            $chunk_number = !empty($settings->F22_no_of_product_per_page) ? $settings->F22_no_of_product_per_page : 25;
            $chunks = $details->chunk($chunk_number);
            $pre_page_total_purchase = 0;
            $pre_page_total_sale = 0;
            $grand_page_total_purchase = 0;
            $grand_page_total_sale = 0;
        @endphp

        @foreach ($chunks as $pageIndex => $chunk)
            <div class="{{ $loop->last ? 'no-page-break' : 'page-break' }}">
                <table>
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
                        @php
                            $this_page_total_purchase = 0;
                            $this_page_total_sale = 0;
                        @endphp

                        @foreach ($chunk as $item)
                            @php
                                $stock_count = (float) ($item->stock_count ?? 0);
                                $unit_purchase_price = (float) ($item->unit_purchase_price ?? 0);
                                $unit_sale_price = (float) ($item->unit_sale_price ?? 0);

                                $purchase_price_total = isset($item->purchase_price_total)
                                    ? (float) $item->purchase_price_total
                                    : ($unit_purchase_price * $stock_count);
                                $sales_price_total = isset($item->sales_price_total)
                                    ? (float) $item->sales_price_total
                                    : ($unit_sale_price * $stock_count);

                                $this_page_total_purchase += $purchase_price_total;
                                $this_page_total_sale += $sales_price_total;
                            @endphp
                            <tr>
                                <td>{{ $index++ }}</td>
                                <td>{{ $item->product_code ?? '' }}</td>
                                <td>{{ $item->book_no ?? '' }}</td>
                                <td>{{ $item->product ?? '' }}</td>
                                <td>{{ $item->current_stock !== null ? number_format((float) $item->current_stock, 2) : '' }}</td>
                                <td>{{ $item->stock_count !== null ? number_format((float) $item->stock_count, 2) : '' }}</td>
                                <td>{{ number_format($unit_purchase_price, 2) }}</td>
                                <td>{{ number_format($purchase_price_total, 2) }}</td>
                                <td>{{ number_format($unit_sale_price, 2) }}</td>
                                <td>{{ number_format($sales_price_total, 2) }}</td>
                                <td>{{ $item->difference_qty !== null ? number_format((float) $item->difference_qty, 2) : '' }}</td>
                            </tr>
                        @endforeach

                        @php
                            $grand_page_total_purchase += $this_page_total_purchase;
                            $grand_page_total_sale += $this_page_total_sale;
                        @endphp
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7" class="text-red text-bold">@lang('mpcs::lang.total_this_page')</td>
                            <td class="text-red text-bold">{{ number_format($this_page_total_purchase, 2) }}</td>
                            <td></td>
                            <td colspan="2" class="text-red text-bold">{{ number_format($this_page_total_sale, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="7" class="text-red text-bold">@lang('mpcs::lang.total_previous_page')</td>
                            <td class="text-red text-bold">{{ number_format($pre_page_total_purchase, 2) }}</td>
                            <td></td>
                            <td colspan="2" class="text-red text-bold">{{ number_format($pre_page_total_sale, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="7" class="text-red text-bold">@lang('mpcs::lang.grand_total')</td>
                            <td class="text-red text-bold">{{ number_format($grand_page_total_purchase, 2) }}</td>
                            <td></td>
                            <td colspan="2" class="text-red text-bold">{{ number_format($grand_page_total_sale, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="11">@lang('mpcs::lang.confirm_f22')</td>
                        </tr>
                        <tr>
                            <td colspan="7" style="border: 0 !important;">
                                <h5 style="font-weight: bold;">@lang('mpcs::lang.checked_by'): ____________</h5>
                            </td>
                            <td colspan="4" style="border: 0 !important;">
                                <h5 style="font-weight: bold;">@lang('mpcs::lang.received_by'): ____________</h5>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="border: 0 !important;">
                                <h5 style="font-weight: bold;">@lang('mpcs::lang.signature_of_manager'): ____________</h5>
                            </td>
                            <td colspan="4" style="border: 0 !important;">
                                <h5 style="font-weight: bold;">@lang('mpcs::lang.handed_over_by'): ____________</h5>
                            </td>
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
                                                        <td>{{ $pump->pump_name ?? 'N/A' }}</td>
                                                        <td>{{ $pump->closing_meter ? number_format($pump->closing_meter, 3) : '0.000' }}</td>
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
                            <td colspan="7" style="border: 0 !important;">
                                <h5 style="font-weight: bold; margin-top: 10px;">@lang('mpcs::lang.user'): {{ auth()->user()->username }}</h5>
                            </td>
                            <td colspan="4" style="border: 0 !important;">
                                <h5 style="font-weight: bold; text-align:right;">{{ $header->form_no }}-{{ $pageIndex + 1 }}</h5>
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
    </div>
</body>

</html>
