@extends('layouts.app')

@section('title', __('lang_v1.profit_loss_report'))



@section('content')

@include('report.profit_loss')

@endsection



@section('javascript')

<script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>

<script>

    $(document).ready( function() {

        profit_by_products_table = $('#profit_by_products_table').DataTable({

                processing: true,

                serverSide: true,

                "ajax": {

                    "url": "/reports/get-profit/product",

                    "data": function ( d ) {

                        d.start_date = $('#profit_tabs_filter')

                            .data('daterangepicker')

                            .startDate.format('YYYY-MM-DD');

                        d.end_date = $('#profit_tabs_filter')

                            .data('daterangepicker')

                            .endDate.format('YYYY-MM-DD');

                        d.location_id = $('#profit_loss_location_filter').val();

                    }

                },

                columns: [

                    { data: 'product', name: 'P.name'  },

                    { data: 'total_sales', "searchable": false},
                    { data: 'gross_profit', "searchable": false},

                ],

                fnDrawCallback: function(oSettings) {

                    var total_profit = sum_table_col($('#profit_by_products_table'), 'gross-profit');
                    var total_sales = sum_table_col($('#profit_by_products_table'), 'total-sales');

                    $('#profit_by_products_table .footer_total').text(total_profit);
                    $('#profit_by_products_table .footer_total_sales').text(total_sales);



                    __currency_convert_recursively($('#profit_by_products_table'));

                },

            });



        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {

            var target = $(e.target).attr('href');

            if ( target == '#profit_by_categories') {

                if(typeof profit_by_categories_datatable == 'undefined') {

                    profit_by_categories_datatable = $('#profit_by_categories_table').DataTable({

                        processing: true,

                        serverSide: true,

                        "ajax": {

                            "url": "/reports/get-profit/category",

                            "data": function ( d ) {

                                d.start_date = $('#profit_tabs_filter')

                                    .data('daterangepicker')

                                    .startDate.format('YYYY-MM-DD');

                                d.end_date = $('#profit_tabs_filter')

                                    .data('daterangepicker')

                                    .endDate.format('YYYY-MM-DD');

                                d.location_id = $('#profit_loss_location_filter').val();

                            }

                        },

                        columns: [

                            { data: 'category', name: 'C.name'  },

                            { data: 'total_sales', "searchable": false},
                            { data: 'gross_profit', "searchable": false},

                        ],

                        fnDrawCallback: function(oSettings) {

                            var total_profit = sum_table_col($('#profit_by_categories_table'), 'gross-profit');
                            var total_sales = sum_table_col($('#profit_by_categories_table'), 'total-sales');

                            $('#profit_by_categories_table .footer_total_sales').text(total_sales);
                            $('#profit_by_categories_table .footer_total').text(total_profit);
                           



                            __currency_convert_recursively($('#profit_by_categories_table'));

                        },

                    });

                } else {

                    profit_by_categories_datatable.ajax.reload();

                }

            }else if ( target == '#profit_by_sub_categories') {

                if(typeof profit_by_sub_categories_datatable == 'undefined') {

                    profit_by_sub_categories_datatable = $('#profit_by_sub_categories_table').DataTable({

                        processing: true,

                        serverSide: true,

                        "ajax": {

                            "url": "/reports/get-profit/sub-category",

                            "data": function ( d ) {

                                d.start_date = $('#profit_tabs_filter')

                                    .data('daterangepicker')

                                    .startDate.format('YYYY-MM-DD');

                                d.end_date = $('#profit_tabs_filter')

                                    .data('daterangepicker')

                                    .endDate.format('YYYY-MM-DD');

                                d.location_id = $('#profit_loss_location_filter').val();

                            }

                        },

                        columns: [

                            { data: 'category', name: 'C.name'  },

                            { data: 'total_sales', "searchable": false},
                            { data: 'gross_profit', "searchable": false},

                        ],

                        fnDrawCallback: function(oSettings) {

                            var total_profit = sum_table_col($('#profit_by_sub_categories_table'), 'gross-profit');
                            var total_sales = sum_table_col($('#profit_by_sub_categories_table'), 'total-sales');

                            $('#profit_by_sub_categories_table .footer_total_sales').text(total_sales);
                            $('#profit_by_sub_categories_table .footer_total').text(total_profit);
                           



                            __currency_convert_recursively($('#profit_by_sub_categories_table'));

                        },

                    });

                } else {

                    profit_by_sub_categories_datatable.ajax.reload();

                }

            } else if (target == '#profit_by_brands') {

                if(typeof profit_by_brands_datatable == 'undefined') {

                    profit_by_brands_datatable = $('#profit_by_brands_table').DataTable({

                        processing: true,

                        serverSide: true,

                        "ajax": {

                            "url": "/reports/get-profit/brand",

                            "data": function ( d ) {

                                d.start_date = $('#profit_tabs_filter')

                                    .data('daterangepicker')

                                    .startDate.format('YYYY-MM-DD');

                                d.end_date = $('#profit_tabs_filter')

                                    .data('daterangepicker')

                                    .endDate.format('YYYY-MM-DD');

                                d.location_id = $('#profit_loss_location_filter').val();

                            }

                        },

                        columns: [

                            { data: 'brand', name: 'B.name'  },
                            { data: 'total_sales', "searchable": false},

                            { data: 'gross_profit', "searchable": false},

                        ],

                        fnDrawCallback: function(oSettings) {

                            var total_profit = sum_table_col($('#profit_by_brands_table'), 'gross-profit');
                            var total_sales = sum_table_col($('#profit_by_brands_table'), 'total-sales');
                            
                            $('#profit_by_brands_table .footer_total_sales').text(total_sales);

                            $('#profit_by_brands_table .footer_total').text(total_profit);



                            __currency_convert_recursively($('#profit_by_brands_table'));

                        },

                    });

                } else {

                    profit_by_brands_datatable.ajax.reload();

                }

            } else if (target == '#profit_by_locations') {

                if(typeof profit_by_locations_datatable == 'undefined') {

                    profit_by_locations_datatable = $('#profit_by_locations_table').DataTable({

                        processing: true,

                        serverSide: true,

                        "ajax": {

                            "url": "/reports/get-profit/location",

                            "data": function ( d ) {

                                d.start_date = $('#profit_tabs_filter')

                                    .data('daterangepicker')

                                    .startDate.format('YYYY-MM-DD');

                                d.end_date = $('#profit_tabs_filter')

                                    .data('daterangepicker')

                                    .endDate.format('YYYY-MM-DD');

                                d.location_id = $('#profit_loss_location_filter').val();

                            }

                        },

                        columns: [

                            { data: 'location', name: 'L.name'  },
                            { data: 'total_sales', "searchable": false},

                            { data: 'gross_profit', "searchable": false},

                        ],

                        fnDrawCallback: function(oSettings) {

                            var total_profit = sum_table_col($('#profit_by_locations_table'), 'gross-profit');
                            var total_sales = sum_table_col($('#profit_by_locations_table'), 'total-sales');
                            
                            $('#profit_by_locations_table .footer_total_sales').text(total_sales);


                            $('#profit_by_locations_table .footer_total').text(total_profit);



                            __currency_convert_recursively($('#profit_by_locations_table'));

                        },

                    });

                } else {

                    profit_by_locations_datatable.ajax.reload();

                }

            } else if (target == '#profit_by_invoice') {

                if(typeof profit_by_invoice_datatable == 'undefined') {

                    profit_by_invoice_datatable = $('#profit_by_invoice_table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "/reports/get-profit/invoice",
                            data: function(d) {
                                d.start_date = $('#profit_tabs_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                                d.location_id = $('#profit_loss_location_filter').val();
                            }
                        },
                        columns: [
                            { data: 'invoice_no', name: 'sale.invoice_no' },
                            { data: 'final_total', name: 'final_total', render: function(data, type, row) {
                                // ensure numbers are rounded and formatted
                                return parseFloat(data).toFixed(2);
                            }},
                            { data: 'gross_profit', name: 'gross_profit', searchable: false }
                        ],
                        fnDrawCallback: function(oSettings) {
                            // Sum gross_profit using data-orig-value
                            var total_profit = sum_table_col($('#profit_by_invoice_table'), 'gross-profit');
                            $('#profit_by_invoice_table .footer_total').text(parseFloat(total_profit).toFixed(2));

                            // Sum final_total directly from column data
                            var final_total_sum = 0;
                            oSettings.aoData.forEach(function(row) {
                                var val = parseFloat(row._aData.final_total) || 0;
                                final_total_sum += val;
                            });
                            $('#profit_by_invoice_table .footer_final_total').text(final_total_sum.toFixed(2));

                            __currency_convert_recursively($('#profit_by_invoice_table'));
                        },
                    });


                } else {

                    profit_by_invoice_datatable.ajax.reload();

                }

            } else if (target == '#profit_by_date') {

                if(typeof profit_by_date_datatable == 'undefined') {

                    profit_by_date_datatable = $('#profit_by_date_table').DataTable({

                        processing: true,

                        serverSide: true,

                        "ajax": {

                            "url": "/reports/get-profit/date",

                            "data": function ( d ) {

                                d.start_date = $('#profit_tabs_filter')

                                    .data('daterangepicker')

                                    .startDate.format('YYYY-MM-DD');

                                d.end_date = $('#profit_tabs_filter')

                                    .data('daterangepicker')

                                    .endDate.format('YYYY-MM-DD');

                                d.location_id = $('#profit_loss_location_filter').val();

                            }

                        },

                        columns: [

                            { data: 'transaction_date', name: 'sale.transaction_date'  },
                            { data: 'total_sales', "searchable": false},

                            { data: 'gross_profit', "searchable": false},

                        ],

                        fnDrawCallback: function(oSettings) {

                            var total_profit = sum_table_col($('#profit_by_date_table'), 'gross-profit');
                            var total_sales = sum_table_col($('#profit_by_date_table'), 'total-sales');
                            
                            $('#profit_by_date_table .footer_total_sales').text(total_sales);

                            $('#profit_by_date_table .footer_total').text(total_profit);

                            __currency_convert_recursively($('#profit_by_date_table'));

                        },

                    });

                } else {

                    profit_by_date_datatable.ajax.reload();

                }

            } else if (target == '#profit_by_customer') {

                if(typeof profit_by_customers_table == 'undefined') {

                    profit_by_customers_table = $('#profit_by_customer_table').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: "/reports/get-profit/customer",
                            data: function(d) {
                                d.start_date = $('#profit_tabs_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                                d.end_date = $('#profit_tabs_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                                d.location_id = $('#profit_loss_location_filter').val();
                            }
                        },
                        columns: [
                            { data: 'customer', name: 'CU.name' },
                            { data: 'total_sales', searchable: false },
                            { data: 'gross_profit', searchable: false }
                        ],
                        fnDrawCallback: function(oSettings) {
                            var total_profit = sum_table_col($('#profit_by_customer_table'), 'gross-profit');
                            var total_sales = sum_table_col($('#profit_by_customer_table'), 'total-sales');
                            
                            $('#profit_by_customer_table .footer_total_sales').text(parseFloat(total_sales).toFixed(2));
                            $('#profit_by_customer_table .footer_total').text(parseFloat(total_profit).toFixed(2));

                            __currency_convert_recursively($('#profit_by_customer_table'));
                        }
                    });


                } else {

                    profit_by_customers_table.ajax.reload();

                }

            } else if (target == '#profit_by_day') {

            // Get date range
var start_date = $('#profit_tabs_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
var end_date = $('#profit_tabs_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
var location_id = $('#profit_loss_location_filter').val();
var url = '/reports/get-profit/day?start_date=' + start_date + '&end_date=' + end_date + '&location_id=' + location_id;

// Destroy existing table if exists
if ($.fn.DataTable.isDataTable('#profit_by_day_table')) {
    $('#profit_by_day_table').DataTable().clear().destroy();
}

// Initialize DataTable
var profit_by_day_table = $('#profit_by_day_table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: url,
        data: function(d) {
            d.start_date = start_date;
            d.end_date = end_date;
            d.location_id = location_id;
        }
    },
    columns: [
        // Use a fake column index for transaction_date because your JSON doesn't have it
        { data: null, name: 'sale.transaction_date', render: function(row) { 
            // Use the server query ordering date from response
            return row.transaction_date || ''; 
        }},
        { data: 'total_sales', searchable: false, render: function(data, type, row) {
            // Extract numeric value from HTML
            if (typeof data === 'string') {
                let el = document.createElement('div');
                el.innerHTML = data;
                return parseFloat(el.textContent) || 0;
            }
            return parseFloat(data) || 0;
        }},
        { data: 'gross_profit', searchable: false, render: function(data, type, row) {
            // Extract numeric value from HTML
            if (typeof data === 'string') {
                let el = document.createElement('div');
                el.innerHTML = data;
                return parseFloat(el.textContent) || 0;
            }
            return parseFloat(data) || 0;
        }}
    ],
    fnDrawCallback: function() {
        let total_sales = 0;
        let total_profit = 0;

        profit_by_day_table.rows().every(function() {
            let data = this.data();

            // Parse numeric values from HTML
            let sales = 0;
            let profit = 0;

            if (typeof data.total_sales === 'string') {
                let el = document.createElement('div');
                el.innerHTML = data.total_sales;
                sales = parseFloat(el.textContent) || 0;
            } else {
                sales = parseFloat(data.total_sales) || 0;
            }

            if (typeof data.gross_profit === 'string') {
                let el = document.createElement('div');
                el.innerHTML = data.gross_profit;
                profit = parseFloat(el.textContent) || 0;
            } else {
                profit = parseFloat(data.gross_profit) || 0;
            }

            total_sales += sales;
            total_profit += profit;
        });

        // Update footer with rounded totals
        $('#profit_by_day_table .footer_total_sales').text(total_sales.toFixed(2));
        $('#profit_by_day_table .footer_total').text(total_profit.toFixed(2));

        // Convert currency if needed
        __currency_convert_recursively($('#profit_by_day_table'));
    }
});



                

            } else if (target == '#profit_by_products') {

                profit_by_products_table.ajax.reload();

            }

        });

        $('#profit_loss_location_filter').on('change', function () {
            updateProfitLoss();
            $('.nav-tabs li.active')
                .find('a[data-toggle="tab"]')
                .trigger('shown.bs.tab');
        });

    });





</script>

@endsection
