@extends('layouts.app')
@section('title', __('disstocktransfer::lang.list_dis_stock_transfers'))



@section('content')

    <section class="content-header">
        <div class="row">
            <div class="col-md-12 dip_tab">
                <div class="settlement_tabs">
                    <ul class="nav nav-tabs">
                        <li class="@if (empty(session('status.tab'))) active @endif" style="margin-left: 20px;">
                            <a style="font-size:13px;" href="#transfer_list" id="transfer-list-link" data-toggle="tab">
                                <i class="fa fa-list"></i>
                                <strong>{{ __('disstocktransfer::lang.list_dis_stock_transfers') }}</strong>
                            </a>
                        </li>
                        <li class="@if (session('status.tab') == 'product_wise') active @endif">
                            <a style="font-size:13px;" href="#product_wise" id="product-wise-link" data-toggle="tab">
                                <i class="fa fa-cubes"></i>
                                <strong>{{ __('disstocktransfer::lang.list_dis_stock_transfers_product_wise') }}</strong>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Add button on top-right -->
        {{-- <div class="row" style="margin-top:20px;">
            <div class="col-md-12 text-right">
                <a href="{{ action('\Modules\DisStockTransfer\Http\Controllers\DisStockTransferController@create') }}"
                    class="btn btn-primary btn-sm add-btn" data-tab="transfer_list">
                    <i class="fa fa-plus"></i> {{ __('disstocktransfer::lang.add') }}
                </a>

                <a href="{{ action('\Modules\DisStockTransfer\Http\Controllers\DisStockTransferController@create') }}?tab=product_wise"
                    class="btn btn-primary btn-sm add-btn" data-tab="product_wise" style="display:none;">
                    <i class="fa fa-plus"></i> {{ __('disstocktransfer::lang.add') }}
                </a>
            </div>
        </div> --}}

        <div class="tab-content" style="margin-top:20px;">
            <!-- Stock Transfer List -->
            <div class="tab-pane @if (empty(session('status.tab'))) active @endif" id="transfer_list">
                @include('disstocktransfer::partials.transfer_table')
            </div>

            <!-- Product Wise Stock Transfer -->
            <div class="tab-pane @if (session('status.tab') == 'product_wise') active @endif" id="product_wise">
                {{-- <h2 class="text-center" style="font-size: 20px; font-weight: 500; margin-bottom: 10px;">All Dis Stock Transfers - Product Wise</h2> --}}
                @include('disstocktransfer::partials.product_wise_table')
            </div>
        </div>
        <div class="modal fade" id="view_transfer_modal" tabindex="-1"></div>
    </section>

@endsection

@section('javascript')
    <script>
        $(document).on('click', '.view-transfer', function() {
            let url = $(this).data('href');

            $('#view_transfer_modal').load(url, function() {
                $(this).modal('show');
            });
        });

        $(document).ready(function() {

            // Default Stock Transfer Table
            let table = $('#dis_stock_transfer_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ action('\Modules\DisStockTransfer\Http\Controllers\DisStockTransferController@index') }}",
                columns: [{
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'reference_no',
                        name: 'reference_no'
                    },
                    {
                        data: 'location_name',
                        name: 'location_name'
                    },
                    {
                        data: 'store_name',
                        name: 'store_name'
                    },
                    {
                        data: 'to_store_name',
                        name: 'to_store_name'
                    },
                    {
                        data: 'total_amount',
                        name: 'total_amount',
                        className: 'text-right',
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();
                    var intVal = function(i) {
                        return typeof i === 'string' ?
                            i.replace(/[^0-9.\-]/g, '') * 1 :
                            typeof i === 'number' ?
                            i : 0;
                    };
                    var pageTotal = api
                        .column(5, { page: 'current' })
                        .data()
                        .reduce(function(a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
                    $(api.column(5).footer()).html(
                        pageTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})
                    );
                }
            });

            // Product Wise Table
            let productTable = $('#dis_stock_transfer_product_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ action('\Modules\DisStockTransfer\Http\Controllers\DisStockTransferController@productWiseList') }}",
                columns: [{
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'reference_no',
                        name: 'reference_no'
                    },
                    {
                        data: 'location_name',
                        name: 'location_name'
                    },
                    {
                        data: 'product_code',
                        name: 'product_code'
                    },
                    {
                        data: 'product_name',
                        name: 'product_name'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity',
                        className: 'text-right'
                    },
                    {
                        data: 'store_name',
                        name: 'store_name'
                    },
                    {
                        data: 'vehicle_no',
                        name: 'vehicle_no'
                    },
                    {
                        data: 'total_amount',
                        name: 'total_amount',
                        className: 'text-right',
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                language: {
                    emptyTable: "No data available in table"
                },
                order: [
                    [0, 'desc']
                ], // Sort by date descending
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();

                    // Remove the formatting to get integer data for summation
                    var intVal = function(i) {
                        return typeof i === 'string' ?
                            i.replace(/[\$,]/g, '') * 1 :
                            typeof i === 'number' ?
                            i : 0;
                    };

                    // Total quantity over this page
                    var pageQty = api
                        .column(5, {
                            page: 'current'
                        })
                        .data()
                        .reduce(function(a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                    // Total amount over this page
                    var pageTotal = api
                        .column(8, {
                            page: 'current'
                        })
                        .data()
                        .reduce(function(a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                    // Update footer
                    $(api.column(5).footer()).html(
                        'Page Total: ' + pageQty.toFixed(2)
                    );

                    $(api.column(8).footer()).html(
                        'Page Total: ' + pageTotal.toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })
                    );
                }
            });

        });
    </script>
@endsection
