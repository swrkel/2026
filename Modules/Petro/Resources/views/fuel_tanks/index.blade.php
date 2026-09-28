@extends('layouts.app')

@section('title', __('petro::lang.tank_management'))



@section('content')

<style>
/* S406-010: Fuel Tanks tables compact/full-page and Column Visibility safety. */
#tank_transaction_details_table, #tank_transaction_summary_table {
    width: 100% !important;
    min-width: 980px !important;
    table-layout: fixed !important;
}
#tank_transaction_details_table th, #tank_transaction_details_table td,
#tank_transaction_summary_table th, #tank_transaction_summary_table td {
    font-size: 10px !important;
    padding: 4px 4px !important;
    line-height: 1.12 !important;
    white-space: normal !important;
    overflow-wrap: anywhere !important;
    vertical-align: middle !important;
}
#tank_transaction_details_table th:nth-child(1), #tank_transaction_details_table td:nth-child(1) { width: 86px !important; }
#tank_transaction_details_table th:nth-child(2), #tank_transaction_details_table td:nth-child(2) { width: 80px !important; }
#tank_transaction_details_table th:nth-child(3), #tank_transaction_details_table td:nth-child(3) { width: 82px !important; }
#tank_transaction_details_table th:nth-child(7), #tank_transaction_details_table td:nth-child(7) { width: 92px !important; }
#tank_transaction_details_table th:nth-child(8), #tank_transaction_details_table td:nth-child(8),
#tank_transaction_details_table th:nth-child(9), #tank_transaction_details_table td:nth-child(9),
#tank_transaction_details_table th:nth-child(10), #tank_transaction_details_table td:nth-child(10),
#tank_transaction_details_table th:nth-child(11), #tank_transaction_details_table td:nth-child(11),
#tank_transaction_details_table th:nth-child(12), #tank_transaction_details_table td:nth-child(12) { width: 74px !important; }
.dt-button-collection { z-index: 99999 !important; }
.dt-button-collection .buttons-columnVisibility { display: block !important; text-align: left !important; width: 100% !important; }
</style>


@php
                    
    $business_id = request()
        ->session()
        ->get('user.business_id');
    
    $pacakge_details = [];
        
    $subscription = Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
    if (!empty($subscription)) {
        $pacakge_details = $subscription->package_details;
    }

@endphp

<section class="content-header main-content-inner">

    <div class="row">

        <div class="col-md-12 dip_tab">

            <div class="settlement_tabs">

                <ul class="nav nav-tabs">

                    <li class="active" style="margin-left: 20px;">

                        <a style="font-size:13px;" href="#fuel_tanks" class="" data-toggle="tab">

                            <i class="fa fa-superpowers"></i> <strong>@lang('petro::lang.fuel_tanks')</strong>

                        </a>

                    </li>

                    <li class="" style="margin-left: 20px;">

                        <a style="font-size:13px;" href="#tank_transactions_details" class="" data-toggle="tab">

                            <i class="fa fa-info-circle"></i>

                            <strong>@lang('petro::lang.tank_transactions_details')</strong>

                        </a>

                    </li>

                    <li class="" style="margin-left: 20px;">

                        <a style="font-size:13px;" href="#tank_transactions_summary" class="" data-toggle="tab">

                            <i class="fa fa-exchange"></i>

                            <strong>@lang('petro::lang.tank_transactions_summary')</strong>

                        </a>

                    </li>
                    
                    @if(!empty($pacakge_details['edit_settlement_date']))
                    
                     <li class="" style="margin-left: 20px;">

                        <a style="font-size:13px;" href="#edit_settlement_date" class="" data-toggle="tab">

                            <i class="fa fa-exchange"></i>

                            <strong>@lang('superadmin::lang.edit_settlement_date')</strong>

                        </a>

                    </li>
                    
                    @endif

                </ul>

            </div>

        </div>

    </div>

    <div class="tab-content">

        <div class="tab-pane active" id="fuel_tanks">

            @if(!empty($message)) {!! $message !!} @endif

            @include('petro::fuel_tanks.fuel_tanks')

        </div>

        <div class="tab-pane" id="tank_transactions_details">

            @if(!empty($message)) {!! $message !!} @endif

            @include('petro::tanks_transaction_details.tank_transactions_details')

        </div>

        <div class="tab-pane" id="tank_transactions_summary">

            @if(!empty($message)) {!! $message !!} @endif

            @include('petro::tanks_transaction_details.tank_transactions_summary')

        </div>
        
        @if(!empty($pacakge_details['edit_settlement_date']))
        
        <div class="tab-pane" id="edit_settlement_date">

            @if(!empty($message)) {!! $message !!} @endif

            @include('petro::edit_settlement_date.index')

        </div>
        
        @endif

    </div>



    <div class="modal fade pump_modal" role="dialog" aria-labelledby="gridSystemModalLabel">

    </div>

    <div class="modal fade fuel_tank_modal" role="dialog" aria-labelledby="gridSystemModalLabel">

    </div>

</section>

@endsection

@section('javascript')

<script type="text/javascript">

    $(document).ready( function(){

    var columns = [

            { data: 'transaction_date', name: 'transaction_date' },
            
            { data: 'location_name', name: 'business_locations.name' },

            { data: 'fuel_tank_number', name: 'fuel_tank_number' },

            { data: 'product_name', name: 'products.name' },

            { data: 'storage_volume', name: 'storage_volume' },

            { data: 'new_balance', name: 'new_balance' },

            { data: 'bulk_tank', name: 'bulk_tank' },

            { data: 'action', searchable: false, orderable: false },

        ];

  

    fuel_tanks_table = $('#fuel_tanks_table').DataTable({

        processing: true,

        serverSide: true,

        aaSorting: [[0, 'desc']],
        
        ajax: {

                url: "{{action('\Modules\Petro\Http\Controllers\FuelTankController@index')}}",

                data: function(d) {

                    d.fuel_tank_number =  $('#fueltanks_tank_number').val();
                    d.location_id =  $('#fueltanks_location_id').val();

                },

            },

        columnDefs: [ {

            "targets": 7,

            "orderable": false,

            "searchable": false

        },
        {

            "targets": 1,

            "visible": false

        } 
        ],

        @include('layouts.partials.datatable_export_button')

        columns: columns,

        fnDrawCallback: function(oSettings) {

        

        },

    });
    
    $('#fueltanks_tank_number').change(function(){

        fuel_tanks_table.ajax.reload();

    });



    $(document).on('click', 'a.delete_tank_button', function(e) {

		var page_details = $(this).closest('div.page_details')

		e.preventDefault();

        swal({

            title: LANG.sure,

            icon: 'warning',

            buttons: true,

            dangerMode: true,

        }).then(willDelete => {

            if (willDelete) {

                var href = $(this).attr('href');

                var data = $(this).serialize();

                $.ajax({

                    method: 'DELETE',

                    url: href,

                    dataType: 'json',

                    data: data,

                    success: function(result) {

                        if (result.success == true) {

                            toastr.success(result.msg);

                        } else {

                            toastr.error(result.msg);

                        }

                        fuel_tanks_table.ajax.reload();

                    },

                });

            }

        });

    });

});

</script>



<script type="text/javascript">

    if ($('#transaction_details_date_range').length == 1) {

        $('#transaction_details_date_range').daterangepicker(dateRangeSettings, function(start, end) {

            $('#transaction_details_date_range').val(

                start.format(moment_date_format) + ' - ' + end.format(moment_date_format)

            );
            
            tank_transaction_details_table.ajax.reload();

        });

        $('#custom_date_apply_button').on('click', function() {
            if($('#target_custom_date_input').val() == "transaction_details_date_range"){
                let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                if (startDate.length === 10 && endDate.length === 10) {
                    let formattedStartDate = moment(startDate).format(moment_date_format);
                    let formattedEndDate = moment(endDate).format(moment_date_format);

                    $('#transaction_details_date_range').val(
                        formattedStartDate + ' ~ ' + formattedEndDate
                    );

                    $('#transaction_details_date_range').data('daterangepicker').setStartDate(moment(startDate));
                    $('#transaction_details_date_range').data('daterangepicker').setEndDate(moment(endDate));
                    
                    tank_transaction_details_table.ajax.reload();

                    $('.custom_date_typing_modal').modal('hide');
                } else {
                    alert("Please select both start and end dates.");
                }
            }
        });
        $('#transaction_details_date_range').on('apply.daterangepicker', function(ev, picker) {
            if (picker.chosenLabel === 'Custom Date Range') {
                $('#target_custom_date_input').val('transaction_details_date_range');
                $('.custom_date_typing_modal').modal('show');
            }
        });

        $('#transaction_details_date_range').on('cancel.daterangepicker', function(ev, picker) {

            $('#transaction_details_date_range').val('');

        });

        $('#transaction_details_date_range')

            .data('daterangepicker')

            .setStartDate(moment().startOf('month'));

        $('#transaction_details_date_range')

            .data('daterangepicker')

            .setEndDate(moment().endOf('month'));

    }



  



    $(document).ready( function(){

      var tank_transaction_details_columns = [
    { data: 'created_at', name: 'created_at', searchable: false },
    { data: 'location_name', name: 'business_locations.name' },
    { data: 'transaction_date', name: 'transaction_date' },
    { data: 'fuel_tank_number', name: 'fuel_tanks.fuel_tank_number' },
    { data: 'product_name', name: 'products.name' },
    { data: 'ref_no', name: 'ref_no' },
    { data: 'purchase_order_no', name: 'purchase_order_no' },
    { data: 'opening_balance_qty', name: 'opening_balance_qty' }, // Starting Qty
    { data: 'purchase_qty', name: 'tank_purchase_lines.quantity', searchable: false },
    { data: 'testing_qty', name: 'testing_qty', orderable: false, searchable: false }, // Testing Qty
    { data: 'sold_qty', name: 'tank_sell_lines.quantity', searchable: false },
    { data: 'balance_qty', name: 'balance_qty', searchable: false, sortable: false }
];


    

        tank_transaction_details_table = $('#tank_transaction_details_table').DataTable({

            processing: true,

            serverSide: true,

            pageLength: 25, 

            deferRender: true,

            ordering: false,

            order: [[0, 'desc']],

            ajax: {

                url: '/petro/tanks-transaction-details',

                data: function(d) {

                    d.start_date = $('input#transaction_details_date_range')

                        .data('daterangepicker')

                        .startDate.format('YYYY-MM-DD');

                    d.end_date = $('input#transaction_details_date_range')

                        .data('daterangepicker')

                        .endDate.format('YYYY-MM-DD');

                    d.location_id =  $('#transaction_details_location_id').val();

                    d.fuel_tank_number =  $('#transaction_details_tank_number').val();

                    d.product_id =  $('#transaction_details_product_id').val();

                    d.settlement_id =  $('#transaction_details_settlement_id').val();

                    d.purchase_no =  $('#transaction_details_purhcase_no').val();

                },

            },
            
            columnDefs: [
                { targets: [0, 2, 6], className: 's406-compact-col' }
            ],

            columns: tank_transaction_details_columns,
            fnDrawCallback: function(oSettings) {
                var purchase_sum = sum_table_col($('#tank_transaction_details_table'), 'purchase_qty_transaction');
                var sold_sum = sum_table_col($('#tank_transaction_details_table'), 'sold_qty_transaction');
                var testing_sum = sum_table_col($('#tank_transaction_details_table'), 'testing_qty_transaction'); // ✅ new line

                // Set footer values
                $('#footer_transaction_total_purchase_qty').text(purchase_sum);
                $('#footer_transaction_total_testing_qty').text(testing_sum); // ✅ new footer
                $('#footer_transaction_sold_qty').text(sold_sum);

                __currency_convert_recursively($('#tank_transaction_details_table'));
            },
            

        });
        
        tank_transaction_details_table.columns.adjust();

    });



    $('#transaction_details_date_range, #transaction_details_location_id, #transaction_details_tank_number, #transaction_details_product_id, #transaction_details_settlement_id, #transaction_details_purhcase_no').change(function(){

        tank_transaction_details_table.ajax.reload();

    });

</script>

<script type="text/javascript">

    if ($('#transaction_summary_date_range').length == 1) {

        $('#transaction_summary_date_range').daterangepicker(dateRangeSettings, function(start, end) {

            $('#transaction_summary_date_range').val(

                start.format(moment_date_format) + ' - ' + end.format(moment_date_format)

            );
            
            tank_transaction_summary_table.ajax.reload();

        });

        $('#custom_date_apply_button').on('click', function() {
            if($('#target_custom_date_input').val() == "transaction_summary_date_range"){
                let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                if (startDate.length === 10 && endDate.length === 10) {
                    let formattedStartDate = moment(startDate).format(moment_date_format);
                    let formattedEndDate = moment(endDate).format(moment_date_format);

                    $('#transaction_summary_date_range').val(
                        formattedStartDate + ' ~ ' + formattedEndDate
                    );

                    $('#transaction_summary_date_range').data('daterangepicker').setStartDate(moment(startDate));
                    $('#transaction_summary_date_range').data('daterangepicker').setEndDate(moment(endDate));
                    
                    tank_transaction_summary_table.ajax.reload();

                    $('.custom_date_typing_modal').modal('hide');
                } else {
                    alert("Please select both start and end dates.");
                }
            }
        });
        $('#transaction_summary_date_range').on('apply.daterangepicker', function(ev, picker) {
            if (picker.chosenLabel === 'Custom Date Range') {
                $('#target_custom_date_input').val('transaction_summary_date_range');
                $('.custom_date_typing_modal').modal('show');
            }
        });

        $('#transaction_summary_date_range').on('cancel.daterangepicker', function(ev, picker) {

            $('#transaction_summary_date_range').val('');

        });

        $('#transaction_summary_date_range')

            .data('daterangepicker')

            .setStartDate(moment().startOf('month'));

        $('#transaction_summary_date_range')

            .data('daterangepicker')

            .setEndDate(moment().endOf('month'));

    }



    $('#transaction_summary_date_range, #transaction_summary_location_id, #transaction_summary_tank_number, #transaction_summary_product_id').change(function(){

        tank_transaction_summary_table.ajax.reload();

    });





    $(document).ready( function(){

        var tank_transaction_summary_columns = [

                { data: 'transaction_date', name: 'transaction_date', orderable: false },
                
                { data: 'location_name', name: 'business_locations.name'},

                { data: 'fuel_tank_number', name: 'fuel_tanks.fuel_tank_number' },

                { data: 'product_name', name: 'products.name' },

                { data: 'starting_qty', name: 'starting_qty', searchable: false, sortable: false},

                { data: 'purchase_qty', name: 'purchase_qty', searchable: false, sortable: false},

                { data: 'testing_qty', name: 'testing_qty', searchable: false, sortable: false},

                { data: 'sold_qty', name: 'sold_qty', searchable: false, sortable: false},

                { data: 'balance_qty', name: 'balance_qty', searchable: false, sortable: false},

            ];

    
        if ($.fn.DataTable.isDataTable('#tank_transaction_summary_table')) {
            $('#tank_transaction_summary_table').DataTable().destroy();
        }
        tank_transaction_summary_table = $('#tank_transaction_summary_table').DataTable({

             processing: true,

            serverSide: true,

            pageLength: 25, 

            deferRender: true,

            // order: [[0, 'desc']],

            ajax: {

                url: '/petro/tanks-transaction-summary',
                cache: false,

                data: function(d) {

                    d.start_date = $('input#transaction_summary_date_range')

                        .data('daterangepicker')

                        .startDate.format('YYYY-MM-DD');

                    d.end_date = $('input#transaction_summary_date_range')

                        .data('daterangepicker')

                        .endDate.format('YYYY-MM-DD');

                    d.location_id =  $('#transaction_summary_location_id').val();

                    d.fuel_tank_number =  $('#transaction_summary_tank_number').val();

                    d.product_id =  $('#transaction_summary_product_id').val();

                },

            },
            
            columnDefs: [
                { targets: [0, 2, 6], className: 's406-compact-col' }
            ],

            columns: tank_transaction_summary_columns,

            rowCallback: function( row, data, index ) {

                // if (data['balance_qty'] == 0) {

                //     $(row).hide();

                // }

            },
            fnDrawCallback: function(oSettings) {
                var purchase_summary = sum_table_col($('#tank_transaction_summary_table'), 'purchase_qty');
                var testing_summary = sum_table_col($('#tank_transaction_summary_table'), 'testing_qty');
                var sold_summary = sum_table_col($('#tank_transaction_summary_table'), 'sold_qty');
                $('#footer_total_purchase_qty').text(purchase_summary);
                $('#footer_testing_qty').text(testing_summary);
                $('#footer_sold_qty').text(sold_summary);

                __currency_convert_recursively($('#tank_transaction_details_table'));
            },

        });
        
        tank_transaction_summary_table.column(1).visible(false);



        $('.add_fuel_tank').click(function(){

            $('.fuel_tank_modal').modal({

                backdrop : 'static',

                keyboard: false

            })

        })

    });

</script>

<script type="text/javascript">

    if ($('#edit_settlement_date_range').length == 1) {

        $('#edit_settlement_date_range').daterangepicker(dateRangeSettings, function(start, end) {

            $('#edit_settlement_date_range').val(

                start.format(moment_date_format) + ' - ' + end.format(moment_date_format)

            );
            
            edit_settlement_date_table.ajax.reload();

        });

        $('#custom_date_apply_button').on('click', function() {
            if($('#target_custom_date_input').val() == "edit_settlement_date_range"){
                let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                if (startDate.length === 10 && endDate.length === 10) {
                    let formattedStartDate = moment(startDate).format(moment_date_format);
                    let formattedEndDate = moment(endDate).format(moment_date_format);

                    $('#edit_settlement_date_range').val(
                        formattedStartDate + ' ~ ' + formattedEndDate
                    );

                    $('#edit_settlement_date_range').data('daterangepicker').setStartDate(moment(startDate));
                    $('#edit_settlement_date_range').data('daterangepicker').setEndDate(moment(endDate));
                    
                    edit_settlement_date_table.ajax.reload();

                    $('.custom_date_typing_modal').modal('hide');
                } else {
                    alert("Please select both start and end dates.");
                }
            }
        });
        $('#edit_settlement_date_range').on('apply.daterangepicker', function(ev, picker) {
            if (picker.chosenLabel === 'Custom Date Range') {
                $('#target_custom_date_input').val('edit_settlement_date_range');
                $('.custom_date_typing_modal').modal('show');
            }
        });

        $('#edit_settlement_date_range').on('cancel.daterangepicker', function(ev, picker) {

            $('#edit_settlement_date_range').val('');

        });

        $('#edit_settlement_date_range')

            .data('daterangepicker')

            .setStartDate(moment().startOf('month'));

        $('#edit_settlement_date_range')

            .data('daterangepicker')

            .setEndDate(moment().endOf('month'));

    }



    $('#edit_settlement_date_range, #edit_settlement_id').change(function(){

        edit_settlement_date_table.ajax.reload();

    });





    $(document).ready( function(){

        var edit_settlement_date_columns = [

                { data: 'transaction_date', name: 'transactions.transaction_date' },

                { data: 'settlement_no', name: 'settlements.settlement_no' },

                { data: 'fuel_tank_number', name: 'fuel_tanks.fuel_tank_number'},

                { data: 'product_name', name: 'products.name' },

                { data: 'created_at', name: 'created_at'},

                { data: 'action', name: 'action'},

            ];

    

        edit_settlement_date_table = $('#edit_settlement_date_table').DataTable({

            processing: true,

            serverSide: true,

            ajax: {

                url: '/petro/settlement/get-meter-sales',

                data: function(d) {
                    console.log(d);

                    d.start_date = $('input#edit_settlement_date_range')

                        .data('daterangepicker')

                        .startDate.format('YYYY-MM-DD');

                    d.end_date = $('input#edit_settlement_date_range')

                        .data('daterangepicker')

                        .endDate.format('YYYY-MM-DD');

                    d.settlement_no =  $('#edit_settlement_id').val();

                },

            },

            columns: edit_settlement_date_columns,

            rowCallback: function( row, data, index ) {

                // if (data['balance_qty'] == 0) {

                //     $(row).hide();

                // }

            },

        });



    });

</script>

@endsection
