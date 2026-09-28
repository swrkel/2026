@extends('layouts.app')
@section('title', __('mpcs::lang.F22StockTaking_form'))

@section('content')
    <!-- Main content -->
    <style>
        .pump-section table {
            display: block;
            overflow-x: auto;
        }

        .pumps-container {
            display: flex;
            gap: 10px;
            /* Horizontal spacing between pump sections */
            align-items: stretch;
            /* Ensures child elements take full height */
        }

        .no-wrap {
            white-space: nowrap;
        }

        .column-50 {
            width: 25%;
        }

        .pump-section {
            flex: 1;
            /* Each pump section takes equal width */
            border-right: 3px solid skyblue;
            /* Vertical divider */
            padding-right: 10px;
            /* Space between content and divider */
            display: flex;
            flex-direction: column;
            height: 100%;
            /* Ensure full height */
        }

        .form-input {
            width: 50%;
            padding: 1px;
            box-sizing: border-box;
        }


        .padding-20 {
            padding: 20px;
        }
    </style>

    <div class="card padding-20">
        <div class="card-header d-flex justify-content-end">
            <a href="{{ url('/mpcs/F22_stock_taking') }}#list_f22_stock_taking_tab" class="btn btn-primary">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Form Number Section -->
                <div class="col-md-8 offset-md-2 text-center">
                    <!-- Location name on top -->
                    <h5 style="font-weight: bold;">
                        {{ $header->location_name }}
                    </h5>
                    <!-- Form number below location name -->
                    <h5 style="font-weight: bold;" class="text-red">
                        @lang('mpcs::lang.f22_form_no') : {{ $header->form_no }}
                    </h5>
                </div>

                <!-- Date and Time -->
                <div class="col-md-3 text-center">
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

            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="form_22_table" style="width: 100%;">
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
                                    $grand_page_total_purchase = 0;
                                    $grand_page_total_sale = 0;
                                @endphp

                                @foreach ($details as $index => $item)
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

                                        $grand_page_total_purchase += $purchase_price_total;
                                        $grand_page_total_sale += $sales_price_total;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->product_code ?? '' }}</td>
                                        <td>{{ $item->book_no ?? '' }}</td>
                                        <td>{{ $item->product ?? '' }}</td>
                                        <td class="text-right">
                                            {{ $item->current_stock !== null ? number_format((float) $item->current_stock, 2) : '' }}
                                        </td>
                                        <td class="text-right">
                                            {{ $item->stock_count !== null ? number_format((float) $item->stock_count, 2) : '' }}</td>
                                        <td class="text-right">{{ number_format($unit_purchase_price, 2) }}</td>
                                        <td class="total_purchase_price text-right">
                                            {{ number_format($purchase_price_total, 2) }}</td>
                                        <td class="text-right">{{ number_format($unit_sale_price, 2) }}
                                        </td>
                                        <td class="total_sale_price text-right">{{ number_format($sales_price_total, 2) }}</td>
                                        <td class="text-right">
                                            {{ $item->difference_qty !== null ? number_format((float) $item->difference_qty, 2) : '' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray">
                                    <td class="text-red text-bold" colspan="7">@lang('mpcs::lang.total_this_page')</td>
                                    <td class="text-red text-bold text-right" id="footer_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold text-right" id="footer_total_sale_price"></td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr class="bg-gray">
                                    <td class="text-red text-bold" colspan="7">@lang('mpcs::lang.total_previous_page')
                                    </td>
                                    <td class="text-red text-bold text-right" id="pre_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold text-right" id="pre_total_sale_price"></td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr class="bg-gray">
                                    <td class="text-red text-bold" colspan="7">@lang('mpcs::lang.grand_total')</td>
                                    <td class="text-red text-bold text-right" id="grand_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold text-right" id="grand_total_sale_price"></td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                    <td colspan="11"> @lang('mpcs::lang.confirm_f22')</td>
                                </tr>
                                <tr>
                                    <td colspan="7">
                                        <h5 style="font-weight: bold; margin-bottom: 0px; ">
                                            @lang('mpcs::lang.checked_by'): ____________</h5>
                                    </td>
                                    <td colspan="4">
                                        <h5 style="font-weight: bold; margin-bottom: 0px; ">
                                            @lang('mpcs::lang.received_by'): ____________</h5> <br>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="7">
                                        <h5 style="font-weight: bold; margin-bottom: 0px; ">
                                            @lang('mpcs::lang.signature_of_manager'): ____________</h5>
                                    </td>
                                    <td colspan="4">
                                        <h5 style="font-weight: bold; margin-bottom: 0px; ">
                                            @lang('mpcs::lang.handed_over_by'): ____________</h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="11">
                                        <h5 style="font-weight: bold; margin-top: 10px; ">@lang('mpcs::lang.user'):
                                            {{ auth()->user()->username }}</h5>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pumps & Meters Section -->
            @if($pumps && $pumps->isNotEmpty())
            <div class="row" style="margin-top: 30px;">
                <div class="col-md-12">
                    <h3 style="color:red;">Pumps & Meters</h3>
                    <div class="pumps-container">
                        @foreach($pumps->groupBy('product_name') as $product => $groupedPumps)
                            <div class="pump-section" style="width:100%;">
                                <h4>{{ $product }}</h4>
                                <table class="table table-bordered" style="border: none; width: 100%;">
                                    <tr>
                                        <th class="column-50 no-wrap">Pump Name</th>
                                        <th>Meter Reading</th>
                                    </tr>
                                    @foreach($groupedPumps as $pump)
                                        <tr>
                                            <td>{{ $pump->pump_name }}</td>
                                            <td>{{ number_format($pump->closing_meter, 3) }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
@endsection

@section('javascript')
    <script>
        let form_22_table;
        var prev_purchase_total = 0;
        var prev_sale_total = 0;

        function formatNumberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        $('#form_22_table').DataTable({
            pagingType: 'simple',
            lengthChange: false,
            pageLength: {{ !empty($settings->F22_no_of_product_per_page) ? $settings->F22_no_of_product_per_page : 25 }},
            columnDefs: [{
                "targets": 0,
                "orderable": false,
            }, ],
            drawCallback: function() {
                var api = this.api();
                var info = api.page.info();
                if (!info) return;

                var start = info.start;
                var end = info.end;

                let pagePurchase = 0;
                let pageSale = 0;
                let prevPurchase = 0;
                let prevSale = 0;

                api.rows({ search: 'applied', order: 'applied' }).every(function(rowIdx, tableLoop, containerLoop) {
                    var rowData = this.data();
                    var purchaseVal = parseFloat((rowData[7] || '0').toString().replace(/,/g, '')) || 0;
                    var saleVal = parseFloat((rowData[9] || '0').toString().replace(/,/g, '')) || 0;

                    if (containerLoop >= start && containerLoop < end) {
                        pagePurchase += purchaseVal;
                        pageSale += saleVal;
                    } else if (containerLoop < start) {
                        prevPurchase += purchaseVal;
                        prevSale += saleVal;
                    }
                });

                $('#footer_total_purchase_price').text(formatNumberWithCommas(pagePurchase.toFixed(2)));
                $('#footer_total_sale_price').text(formatNumberWithCommas(pageSale.toFixed(2)));

                $('#pre_total_purchase_price').text(formatNumberWithCommas(prevPurchase.toFixed(2)));
                $('#pre_total_sale_price').text(formatNumberWithCommas(prevSale.toFixed(2)));

                let grand_purchase = (prevPurchase + pagePurchase).toFixed(2);
                let grand_sale = (prevSale + pageSale).toFixed(2);

                $('#grand_total_purchase_price').text(formatNumberWithCommas(grand_purchase));
                $('#grand_total_sale_price').text(formatNumberWithCommas(grand_sale));

                __currency_convert_recursively($('#form_22_table'));
            }
        });
        function sum_table_col(table, class_name) {
            var sum = 0;
            table.find('tbody tr:visible').each(function() {
                var value = $(this).find('td.' + class_name).text();
                value = value.replace(/,/g, '');
                if (!isNaN(value) && value.length !== 0) {
                    sum += parseFloat(value);
                }
            });
            return sum.toFixed(2);
        }
    </script>

@endsection
