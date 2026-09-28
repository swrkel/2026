@extends('layouts.app')

@section('title', __('petrogeneral::lang.tank_management'))



@section('content')

{{--
    IS2053 follow-up: Default Store warning.

    A fuel tank cannot be saved without it - the stock row it writes has a
    NOT NULL store_id - so this says plainly what is wrong and where to fix it,
    instead of letting the save fail with "Something went wrong".

    Only rendered when the business actually needs a default store; see
    FuelTankController::petroGeneralDefaultStoreMissing().
--}}
@if (! empty($pg_default_store_missing))
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning" style="margin-bottom:15px;">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>@lang('petrogeneral::lang.default_store_required_title')</strong>
                <div style="margin-top:5px;">
                    @lang('petrogeneral::lang.default_store_required_help')
                </div>
            </div>
        </div>
    </div>
@endif






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

                            <i class="fa fa-superpowers"></i> <strong>@lang('petrogeneral::lang.fuel_tanks')</strong>

                        </a>

                    </li>

                    {{-- IS2001: these two tabs render unconditionally again.

                        Parcel 3-41 gated them on package_details keys
                        'tanks_transaction_details' and 'tanks_transaction_summary'.
                        Neither key exists anywhere else in the codebase, and the
                        values Manage stores are per-business arrays rather than
                        scalars - so no form of !empty() on that name can be relied
                        on. The gate hid both tabs while the Manage page showed them
                        ENABLED, and that has now been reported four times running.

                        The Petro and PetroDirect copies of this same view render
                        these tabs with no gate at all, which is how this file also
                        behaved before 3-41. Restoring that is the correct state.

                        Gating can be reinstated once the real key name is confirmed
                        from subscriptions.package_details - not before. --}}
                    <li class="" style="margin-left: 20px;">

                        <a style="font-size:13px;" href="#tank_transactions_details" class="" data-toggle="tab">

                            <i class="fa fa-info-circle"></i>

                            <strong>@lang('petrogeneral::lang.tank_transactions_details')</strong>

                        </a>

                    </li>

                    <li class="" style="margin-left: 20px;">

                        <a style="font-size:13px;" href="#tank_transactions_summary" class="" data-toggle="tab">

                            <i class="fa fa-exchange"></i>

                            <strong>@lang('petrogeneral::lang.tank_transactions_summary')</strong>

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

                    {{--
                        Tank Transfers is an independent Tank Management page.
                        It must not depend on the unrelated edit_settlement_date
                        subscription option. Keeping it inside that @if made the
                        tab disappear whenever Edit Settlement Date was disabled.
                    --}}
                    <li class="" style="margin-left: 20px;">

                        <a style="font-size:13px;" href="#tank_transfers_list" class="" data-toggle="tab">

                            <i class="fa fa-exchange"></i>

                            <strong>@lang('petrogeneral::lang.tank_transfers')</strong>

                        </a>

                    </li>

                </ul>

            </div>

        </div>

    </div>

    <div class="tab-content">

        <div class="tab-pane active" id="fuel_tanks">

            @if(!empty($message)) {!! $message !!} @endif

            @include('petrogeneral::fuel_tanks.fuel_tanks')

        </div>

        <div class="tab-pane" id="tank_transactions_details">

            @if(!empty($message)) {!! $message !!} @endif

            @include('petrogeneral::tanks_transaction_details.tank_transactions_details')

        </div>

        <div class="tab-pane" id="tank_transactions_summary">

            @if(!empty($message)) {!! $message !!} @endif

            @include('petrogeneral::tanks_transaction_details.tank_transactions_summary')

        </div>
        
        @if(!empty($pacakge_details['edit_settlement_date']))
        
        <div class="tab-pane" id="edit_settlement_date">

            @if(!empty($message)) {!! $message !!} @endif

            @include('petrogeneral::edit_settlement_date.index')

        </div>
        
        @endif

        {{-- Tank Transfers must always render with Tank Management. --}}
        @include('petrogeneral::fuel_tanks.tank_transfers_tab')

    </div>



    <div class="modal fade pump_modal" role="dialog" aria-labelledby="gridSystemModalLabel">

    </div>

    <div class="modal fade fuel_tank_modal" role="dialog" aria-labelledby="gridSystemModalLabel">

    </div>

</section>

@endsection

@section('javascript')

{{-- IS2001: show the outcome of a tank transfer save.

     TankTransferController@store returns
         redirect()->back()->with('status', $output)
     for success and for every refusal - location mismatch, product mismatch,
     insufficient balance, and now the validation messages too. Whichever page
     the Add modal was opened from is the page redirected back to, so BOTH the
     Tank Management tab and the standalone List Tank Transfer page have to
     render this. Only one of them did, which is why saving from the tab looked
     like nothing happened at all.

     $errors is rendered as well: a validation failure that reaches Laravel's
     own handler populates that rather than 'status'. --}}
@if(session('status'))
<script type="text/javascript">
    $(document).ready(function () {
        @if(session('status')['success'])
            var $t = toastr.success({!! json_encode(session('status')['msg']) !!});
            if ($t && $t.length) {
                $t.attr('style', ($t.attr('style') || '')
                    + ';background-color:#28a745 !important;color:#ffffff !important;');
                $t.find('*').attr('style', 'color:#ffffff !important;');
            }
        @else
            toastr.error({!! json_encode(session('status')['msg']) !!});
        @endif
    });
</script>
@endif

@if($errors->any())
<script type="text/javascript">
    $(document).ready(function () {
        toastr.error({!! json_encode(implode(' ', $errors->all())) !!});
    });
</script>
@endif


<script type="text/javascript">
    /*
     * Tank Transfers tab.
     *
     * The list is served by the same endpoint as the standalone List Tank
     * Transfer page. The registered NAMED route is used rather than action(),
     * because the route points at TankTransfer\\TransferListController@index -
     * building the URL from TankTransferController@index resolves somewhere else
     * and the grid never loads.
     */
    $(document).ready(function () {

        var tt_table = null;

        function tt_init_transfers_table() {
            if (tt_table !== null) {
                return;
            }

            tt_table = $('#tt_transfers_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[0, 'desc']],
                ajax: {
                    url: '{{ url('/petro-general/tank-management') }}',
                    data: function (d) {
                        // Internal Tank Management tab request. Keeping this on the
                        // Tank Management URL avoids incorrectly requiring the
                        // separate standalone "List Tank Transfer" page permission.
                        d.petrogeneral_embedded_tank_transfer_data = 1;
                        d.location_id = $('#tt_location_id').val();
                        d.from_tank = $('#tt_from_tank').val();
                        d.to_tank = $('#tt_to_tank').val();

                        // Guarded: DataTables runs this during its first draw, and a
                        // throw here aborts initialisation entirely.
                        var tt_picker = $('#tt_date_range').data('daterangepicker');

                        if ($('#tt_date_range').val() && tt_picker && tt_picker.startDate && tt_picker.endDate) {
                            d.start_date = tt_picker.startDate.format('YYYY-MM-DD');
                            d.end_date = tt_picker.endDate.format('YYYY-MM-DD');
                        }
                    }
                },
                columns: [
                    { data: 'date', name: 'date' },
                    { data: 'location_name', name: 'business_locations.name' },
                    { data: 'transfer_no', name: 'transfer_no' },
                    { data: 't_from_name', name: 't_from.fuel_tank_number' },
                    { data: 'from_qty', name: 'from_qty', searchable: false },
                    { data: 't_to_name', name: 't_to.fuel_tank_number' },
                    { data: 'to_qty', name: 'to_qty', searchable: false },
                    { data: 'product_name', name: 'products.name' },
                    { data: 'quantity', name: 'quantity' },
                    { data: 'user_created', name: 'users.username' }
                ]
            });
        }

        /*
         * IS2001: built on load, not only when the tab is opened.
         *
         * Waiting on shown.bs.tab meant one missed event left a bare table with
         * no search box, no pagination and no "No data available" row - which is
         * indistinguishable from "the transfer did not save". columns.adjust()
         * on show still corrects the widths, which a DataTable created inside a
         * hidden pane measures as zero.
         */
        if ($('#tt_date_range').length === 1) {
            $('#tt_date_range').daterangepicker(dateRangeSettings, function (start, end) {
                $('#tt_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                if (tt_table !== null) { tt_table.ajax.reload(); }
            });

            $('#tt_date_range').on('cancel.daterangepicker', function () {
                $('#tt_date_range').val('');
                if (tt_table !== null) { tt_table.ajax.reload(); }
            });
        }

        tt_init_transfers_table();

        $('a[href="#tank_transfers_list"]').on('shown.bs.tab', function () {
            tt_init_transfers_table();
            if (tt_table !== null) {
                tt_table.columns.adjust();
            }
        });


        $('#tt_location_id, #tt_from_tank, #tt_to_tank').on('change', function () {
            if (tt_table !== null) { tt_table.ajax.reload(); }
        });
    });
</script>


<script type="text/javascript">
/*
 * MA-002: read a date range picker safely.
 *
 * Every table on this page built its request like this:
 *
 *     d.start_date = $('input#some_range').data('daterangepicker')
 *                        .startDate.format('YYYY-MM-DD');
 *
 * If the picker has not been initialised yet - or the input is inside a tab
 * that has not been opened - .data('daterangepicker') is undefined and
 * .startDate throws a TypeError. That happens INSIDE the DataTables data
 * callback, so the request is never built and DataTables reports only
 *
 *     "DataTables warning: table id=... - Ajax error"
 *
 * which says nothing about the real cause. That is the error seen after
 * saving a fuel tank: the save succeeds, the page reloads its four tables,
 * and one of them runs before its picker exists.
 *
 * pgDateRange() returns today's date instead of throwing, so the table loads
 * with a sensible range rather than failing outright. Eighteen call sites on
 * this page had the same weakness.
 */
function pgDateRange(selector, which) {
    try {
        var picker = $(selector).data('daterangepicker');

        if (picker && picker[which] && typeof picker[which].format === 'function') {
            return picker[which].format('YYYY-MM-DD');
        }
    } catch (e) {
        // fall through
    }

    return moment().format('YYYY-MM-DD');
}

/*
 * MA-002: and the same protection for the SETTERS.
 *
 * These run during initialisation:
 *
 *     $('#edit_settlement_date_range').data('daterangepicker')
 *         .setStartDate(moment().startOf('month'));
 *
 * If the picker is not there this throws, and everything AFTER it in that
 * script block stops running - which can leave a table initialised while its
 * own picker never finishes setting up. That is the state that makes the data
 * callback fail on the next reload.
 */
function pgSetDateRange(selector, which, value) {
    try {
        var picker = $(selector).data('daterangepicker');

        if (picker && typeof picker[which] === 'function') {
            picker[which](value);
        }
    } catch (e) {
        // A missing picker must not stop the rest of the page initialising.
    }
}
</script>
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

                url: "{{action('\Modules\PetroGeneral\Http\Controllers\FuelTankController@index')}}",

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

    /*
     * IS1959 #1: reload when the Location filter changes.
     *
     * The table posts d.location_id from #fueltanks_location_id, but only the
     * tank-number filter was wired to reload - so choosing a location did
     * nothing until something else refreshed the table.
     */
    $('#fueltanks_location_id').change(function(){

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

                    pgSetDateRange('#transaction_details_date_range', 'setStartDate', moment(startDate));
                    pgSetDateRange('#transaction_details_date_range', 'setEndDate', moment(endDate));
                    
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

        pgSetDateRange('#transaction_details_date_range', 'setStartDate', moment().startOf('month'));

        pgSetDateRange('#transaction_details_date_range', 'setEndDate', moment().endOf('month'));

    }



  



    $(document).ready( function(){

      /*
       | 8029: clicking the "Click Here" button shows the full Purchase Reference.
       |
       | Delegated from document, because the button is drawn by DataTables and
       | is replaced on every redraw, sort and page change - a direct binding
       | would be lost after the first redraw.
       */
      $(document).off('click.ttRefBtn').on('click.ttRefBtn', '.tt-ref-btn', function(e) {
          e.preventDefault();

          var reference = $(this).data('reference');

          if (!reference) {
              return;
          }

          if (typeof Swal !== 'undefined' && Swal.fire) {
              Swal.fire({
                  title: 'Purchase Reference No',
                  text: reference,
                  icon: 'info',
              });
          } else if (typeof swal === 'function') {
              swal({ title: 'Purchase Reference No', text: reference, icon: 'info' });
          } else {
              window.alert('Purchase Reference No: ' + reference);
          }
      });

      var tank_transaction_details_columns = [
    { data: 'created_at', name: 'created_at', searchable: false },
    { data: 'location_name', name: 'business_locations.name', visible: false },
    { data: 'transaction_date', name: 'transaction_date' },
    { data: 'fuel_tank_number', name: 'fuel_tanks.fuel_tank_number' },
    { data: 'product_name', name: 'products.name' },
    { data: 'ref_no', name: 'ref_no' },
    { data: 'purchase_order_no', name: 'purchase_order_no' },
    // IS1959: className 'tt-num' keeps these figures on a single line while the
    // text columns above are free to wrap - see the CSS in
    // tanks_transaction_details/tank_transactions_details.blade.php. Without it
    // a quantity could be split across two lines, which is far harder to scan.
    { data: 'opening_balance_qty', name: 'opening_balance_qty', className: 'tt-num' }, // Starting Qty
    { data: 'purchase_qty', name: 'tank_purchase_lines.quantity', searchable: false, className: 'tt-num' },
    { data: 'testing_qty', name: 'testing_qty', orderable: false, searchable: false, className: 'tt-num' }, // Testing Qty
    { data: 'sold_qty', name: 'tank_sell_lines.quantity', searchable: false, className: 'tt-num' },
    { data: 'balance_qty', name: 'balance_qty', searchable: false, sortable: false, className: 'tt-num' }
];


    

        tank_transaction_details_table = $('#tank_transaction_details_table').DataTable({

            processing: true,

            serverSide: true,

            pageLength: 25, 

            deferRender: true,

            ordering: false,

            order: [[0, 'desc']],

            ajax: {

                url: '/petro-general/tanks-transaction-details',

                data: function(d) {

                    d.start_date = pgDateRange('input#transaction_details_date_range', 'startDate');

                    d.end_date = pgDateRange('input#transaction_details_date_range', 'endDate');

                    d.location_id =  $('#transaction_details_location_id').val();

                    d.fuel_tank_number =  $('#transaction_details_tank_number').val();

                    d.product_id =  $('#transaction_details_product_id').val();

                    d.settlement_id =  $('#transaction_details_settlement_id').val();

                    d.purchase_no =  $('#transaction_details_purhcase_no').val();

                },

            },
            
            columnDefs: [ 
                {
        
                    "targets": 1,
        
                    "visible": false
        
                } 
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
        
        tank_transaction_details_table.column(1).visible(false);

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

                    pgSetDateRange('#transaction_summary_date_range', 'setStartDate', moment(startDate));
                    pgSetDateRange('#transaction_summary_date_range', 'setEndDate', moment(endDate));
                    
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

        /*
         * MA-002 (IS-1920): Tank Transaction Summary opens on TODAY.
         *
         * It opened on the whole current month - startOf('month') to
         * endOf('month'). Two problems with that:
         *
         *   endOf('month') is in the FUTURE on every day but the last, so the
         *   table was asking for dates that have not happened yet.
         *
         *   This summary builds ONE ROW PER DATE per tank, so a full month is
         *   about 31 rows for every tank before anyone filters anything -
         *   slow to load and hard to read.
         *
         * Today to today gives one row per tank, which is what the screen is
         * for. The date range control is untouched, so any other period is
         * still one click away.
         */
        pgSetDateRange('#transaction_summary_date_range', 'setStartDate', moment());

        pgSetDateRange('#transaction_summary_date_range', 'setEndDate', moment());

        /*
         * MA-002: and SHOW it in the box.
         *
         * pgSetDateRange sets the picker's internal dates, but the visible
         * input is only written in the picker's own callback - which fires when
         * a user chooses a range, not on load. Without this the box reads
         * "Select a date range" while the table below is already showing
         * today's figures, which looks like the filter is not applied.
         */
        $('#transaction_summary_date_range').val(
            moment().format(moment_date_format) + ' - ' + moment().format(moment_date_format)
        );

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

                url: '/petro-general/tanks-transaction-summary',
                cache: false,

                data: function(d) {

                    d.start_date = pgDateRange('input#transaction_summary_date_range', 'startDate');

                    d.end_date = pgDateRange('input#transaction_summary_date_range', 'endDate');

                    d.location_id =  $('#transaction_summary_location_id').val();

                    d.fuel_tank_number =  $('#transaction_summary_tank_number').val();

                    d.product_id =  $('#transaction_summary_product_id').val();

                },

            },
            
            columnDefs: [ 
                {
        
                    "targets": 1,
        
                    "visible": false
        
                } 
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

                // IS1959: this is the SUMMARY table's callback - it was formatting the
                // DETAILS table by mistake, so the summary's own figures and footer
                // totals never had the currency/comma formatting applied.
                __currency_convert_recursively($('#tank_transaction_summary_table'));
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

                    pgSetDateRange('#edit_settlement_date_range', 'setStartDate', moment(startDate));
                    pgSetDateRange('#edit_settlement_date_range', 'setEndDate', moment(endDate));
                    
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

        pgSetDateRange('#edit_settlement_date_range', 'setStartDate', moment().startOf('month'));

        pgSetDateRange('#edit_settlement_date_range', 'setEndDate', moment().endOf('month'));

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

                url: '/petro-general/settlement/get-meter-sales',

                data: function(d) {
                    console.log(d);

                    d.start_date = pgDateRange('input#edit_settlement_date_range', 'startDate');

                    d.end_date = pgDateRange('input#edit_settlement_date_range', 'endDate');

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
