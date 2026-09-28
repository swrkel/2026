@extends('layouts.app')

@section('title', __('petrogeneral::lang.dip_management'))



@section('content')

    <!-- Content Header (Page header) -->

    @php

        $business_id = request()->session()->get('user.business_id');

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

                        {{--
                            Add New Dip is the FIRST tab.

                            NOT data-toggle="tab" and NOT href="#..." - this one
                            opens the form straight away instead of switching to a
                            pane. The .open_add_new_dip_modal class is what the
                            click handler further down listens for.

                            It carries no 'active' class: Dip Report below stays
                            the active pane, so the page still shows the report on
                            load while this sits first in the row.
                        --}}
                        <li class="" style="margin-left: 20px;">

                            <a style="font-size:13px; cursor:pointer;" href="#"
                               class="open_add_new_dip_modal"
                               data-href="{{ action('\Modules\PetroGeneral\Http\Controllers\DipManagementController@addNewDip') }}">

                                <i class="fa fa-plus-circle"></i> <strong>@lang('petrogeneral::lang.add_new_dip')</strong>

                            </a>

                        </li>

                        <li class="active">

                            <a style="font-size:13px;" href="#dip_report" class="" data-toggle="tab">

                                <i class="fa fa-file-o"></i> <strong>@lang('petrogeneral::lang.dip_report')</strong>

                            </a>

                        </li>

                        @if (!empty($pacakge_details['dip_resetting']))
                            <li class="">
                                <a style="font-size:13px;" href="#dip_resetting" data-toggle="tab">

                                    <i class="fa fa-gear"></i> <strong>@lang('petrogeneral::lang.dip_resetting')</strong>

                                </a>

                            </li>
                        @endif

                        @if (!empty($pacakge_details['tank_dip_chart']))
                            <li class="">
                                <a style="font-size:13px;" href="#tank_dip_chart" data-toggle="tab">

                                    <i class="fa fa-gear"></i> <strong>@lang('petrogeneral::lang.tank_dip_chart')</strong>

                                </a>

                            </li>
                        @endif

                    </ul>

                </div>

            </div>

        </div>
        <div class="tab-content">

            <div class="tab-pane active" id="dip_report">

                @if (!empty($message))
                    {!! $message !!}
                @endif

                @include('petrogeneral::dip_management.partials.dip_report')

            </div>
            @if (!empty($pacakge_details['dip_resetting']))
                <div class="tab-pane" id="dip_resetting">

                    @if (!empty($message))
                        {!! $message !!}
                    @endif

                    @include('petrogeneral::dip_management.partials.dip_resetting')

                </div>
            @endif

            @if (!empty($pacakge_details['tank_dip_chart']))
                <div class="tab-pane" id="tank_dip_chart">

                    @if (!empty($message))
                        {!! $message !!}
                    @endif

                    @include('petrogeneral::dip_management.partials.dip_chart')

                </div>
            @endif

        </div>

        <div class="modal fade dip_modal" role="dialog" aria-labelledby="gridSystemModalLabel">

        </div>

    </section>

@endsection

@section('javascript')

    <script type="text/javascript">
        $(document).ready(function() {
            var positiveSum = 0;
            var negativeSum = 0;
            $(document).on('click', '.edit_dip', function(e) {
                e.preventDefault()
                var actionuRL = $(this).data('href');
                $('.dip_modal').load(actionuRL, function() {
                    $(this).modal('show');
                });
            });

            /*
             | S665: open the Add New Dip modal from the new tab.
             |
             | Same .load() pattern as .edit_dip directly above, so the modal is
             | fetched and shown exactly the way the other dip screens do it.
             | The existing 'hidden.bs.modal' handler on .dip_modal empties it
             | afterwards, so reopening always fetches a fresh form.
             */
            $(document).on('click', '.open_add_new_dip_modal', function(e) {
                e.preventDefault();

                var actionuRL = $(this).data('href');
                var $modal = $('.dip_modal');

                /*
                 | Empty BEFORE loading as well as on close.
                 |
                 | Closing normally clears it, but a modal dismissed in another
                 | way - the backdrop, Escape, a page script - can leave the old
                 | form behind. Clearing here guarantees the fresh copy is the
                 | only one on the page, so its ids are the ones jQuery finds.
                 */
                $modal.find('.select2').each(function() {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });
                $modal.empty();

                $modal.load(actionuRL, function() {
                    $(this).modal('show');
                });
            });

            $('.dip_modal').on('show.bs.modal', function() {
                $(this).data('bs.modal').options.backdrop = 'static';
                $(this).data('bs.modal').options.keyboard = false;
            });

            if ($('#report_date_range').length == 1) {

                $('#report_date_range').daterangepicker(dateRangeSettings, function(start, end) {

                    $('#report_date_range').val(

                        start.format(moment_date_format) + ' - ' + end.format(moment_date_format)

                    );
                    dip_report_table.ajax.reload();

                });

                $('#report_date_range').on('cancel.daterangepicker', function(ev, picker) {

                    $('#report_date_range').val('');

                });

                $('#report_date_range')

                    .data('daterangepicker')

                    .setStartDate(moment().startOf('month'));

                $('#report_date_range')

                    .data('daterangepicker')

                    .setEndDate(moment().endOf('month'));

            }

            let dateRangeVal = $('#report_date_range').val();
            if (dateRangeVal && dateRangeVal.includes(' - ')) {
                let date = dateRangeVal.split(' - ');
                $('.report_from_date').text(date[0]);
                $('.report_to_date').text(date[1]);
            }

            $('#location_id').select2();

            $('#tank_id').select2();

            // Before
            // $('#prodcut_id').select2();

            // After
            $('#product_id').select2();



            // var columns = [
            //         { data: 'action', name: 'action' },

            //         { data: 'ref_number', name: 'ref_number' },

            //         { data: 'date_and_time', name: 'date_and_time' },

            //         { data: 'transaction_date', name: 'transaction_date' },

            //         { data: 'location_name', name: 'business_locations.name' },

            //         { data: 'tank_name', name: 'fuel_tanks.fuel_tank_number' },

            //         { data: 'product_name', name: 'products.name' },

            //         { data: 'dip_reading', name: 'dip_reading' },

            //         { data: 'fuel_balance_dip_reading', name: 'fuel_balance_dip_reading' },

            //         { data: 'current_qty', name: 'current_qty' },

            //         { data: 'difference', name: 'difference' },

            //         { data: 'difference_value', name: 'difference_value' },

            //     ];

            var dip_report_table = $('#dip_report_table').DataTable({
                /*
                 | autoWidth off.
                 |
                 | With it on, DataTables measures the content and writes an inline
                 | pixel width onto every <th> - which overrides the column widths
                 | set in the stylesheet. Turning it off lets those widths hold.
                 */
                autoWidth: false,
                processing: true,
                serverSide: true,
                columns: [
                    // Other columns
                    {
                        data: 'sell_price_inc_tax',
                        render: function(data, type, row) {
                            return data ? data : 'N/A'; // Handle null or undefined values
                        }
                    }
                ],
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ action('\Modules\PetroGeneral\Http\Controllers\DipManagementController@getDipReport') }}',
                    data: function(d) {

                        d.location_id = $('select#report_location_id').val();
                        d.tank_id = $('select#report_tank_id').val();
                        d.product_id = $('select#report_product_id').val();
                        d.start_date = $('input#report_date_range').data('daterangepicker').startDate
                            .format('YYYY-MM-DD');
                        d.end_date = $('input#report_date_range').data('daterangepicker').endDate
                            .format('YYYY-MM-DD');
                    }
                },

                fnDrawCallback: function(oSettings) {
                    var api = this.api(),
                        data;
                    var intVal = function(i) {
                        return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i ===
                            'number' ? i : 0;
                    };

                    var total_difference = api.column(10).data().reduce(function(a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                    var total_difference_value = api.column(11).data().reduce(function(a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                    $('#dip_report_table .footer_total .difference_total').text(total_difference
                        .toLocaleString('en-US', {
                            minimumFractionDigits: 2
                        }));
                    $('#dip_report_table .footer_total .difference_value_total').text(
                        total_difference_value.toLocaleString('en-US', {
                            minimumFractionDigits: 2
                        }));

                    positiveSum = 0;
                    negativeSum = 0;
                    api.rows().every(function() {
                        var rowData = this.data();
                        var difference = parseFloat(rowData.difference);

                        if (!isNaN(difference)) {
                            if (difference > 0) {
                                positiveSum += difference;
                            } else if (difference < 0) {
                                negativeSum += difference;
                            }
                        }
                    });

                    $('.report_total_excess').text(positiveSum.toLocaleString('en-US', {
                        minimumFractionDigits: 2
                    }));
                    $('.report_net_difference').text(negativeSum.toLocaleString('en-US', {
                        minimumFractionDigits: 2
                    }));

                },


                columnDefs: [{
                    "targets": 0,
                    "orderable": true,

                    // "searchable": false
                    "searchable": true
                }],
                columns: [{
                        data: 'action',
                        name: 'action'
                    },
                    {
                        data: 'ref_number',
                        name: 'ref_number'
                    },
                    {
                        data: 'date_and_time',
                        name: 'date_and_time'
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'location_name',
                        name: 'business_locations.name'
                    },
                    {
                        data: 'tank_name',
                        name: 'fuel_tanks.fuel_tank_number'
                    },
                    {
                        data: 'product_name',
                        name: 'products.name'
                    },
                    {
                        data: 'dip_reading',
                        name: 'dip_reading'
                    },
                    {
                        data: 'fuel_balance_dip_reading',
                        name: 'fuel_balance_dip_reading'
                    },
                    {
                        data: 'current_qty',
                        name: 'current_qty'
                    },
                    {
                        data: 'difference',
                        name: 'difference'
                    },

                    // Before
                    // { data: 'difference_value', name: 'difference_value' }

                    // After
                    {
                        data: 'difference_value',
                        name: 'difference'
                    }
                ]



            });
            dip_report_table.rows().every(function() {
                var data = this.data();
                var difference = parseFloat(data.difference);

                if (!isNaN(difference) && difference > 0) {
                    positiveSum += difference;
                }
            });

            $(document).on('click', 'a.delete_dipreport_button', function(e) {
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
                                dip_report_table.ajax.reload();
                            },
                        });
                    }
                });
            });

            $(document).on('click', 'a.delete_dipchart_button', function(e) {
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
                                dip_chart_table.ajax.reload();
                            },
                        });
                    }
                });
            });

            $('.report_total_excess').text(positiveSum.toFixed(2));


            $('#report_location_id, #report_tank_id , #report_product_id, #report_date_range').change(function() {

                dip_report_table.ajax.reload();



                if ($('#report_location_id').val() !== '' && $('#report_location_id').val() !== undefined) {

                    $('.report_location_name').text($('#report_location_id :selected').text())

                }

                let dateRangeVal = $('#report_date_range').val();
                if (dateRangeVal && dateRangeVal.includes(' - ')) {
                    let date = dateRangeVal.split(' - ');
                    $('.report_from_date').text(date[0]);
                    $('.report_to_date').text(date[1]);
                }

            });



            $(document).on('change', '#add_dip_tank_id', function() {

                $.ajax({

                    method: 'get',

                    url: '/petro-general/get-tank-balance-by-id/' + $(this).val(),

                    data: {},

                    success: function(result) {
                        console.log('colled');
                        if (result.current_diff == '0.000') {
                            $('#current_qty').val(result.current_stock);
                        } else {
                            $('#current_qty').val(result.current_stock);
                        }



                    },

                });

            });



            $(document).on('change', '#add_reset_tank_id', function() {

                $.ajax({

                    method: 'get',

                    url: '/petro-general/get-tank-balance-by-id/' + $(this).val(),

                    data: {},

                    dataType: 'json',

                    success: function(result) {


                        $('#product_name').val(result.product.name);




                        if (result.current_diff == '0.000') {
                            $('#current_qty').val(result.current_stock);
                        } else {
                            $('#current_qty').val(result.current_stock);
                        }


                        if (result.current_diff == '0.000' || result.reset_new_dip == '0.00') {
                            $('#current_dip_difference').val(result.reset_new_dip);
                        } else {
                            $('#current_dip_difference').val((result.current_diff_for_reseting)
                                .toFixed(2));
                        }





                    },

                });

            });




            /**  

             * @ModifiedBy Afes Oktavianus

             * @DateBy 06-06-2021

             * @Task 3341

             */

            $(document).on('change', '#reset_new_dip', function() {

                let quantity_presicion = $('#quantity_presicion').val()

                let current_qty = $('#current_qty').val();

                let new_dip_qty = $(this).val();

                let qty_to_adjust = new_dip_qty - current_qty;

                var type = '';
                if (parseInt(qty_to_adjust) < 0) {

                    $('#adjustment_type').val('decrease');
                    type = 'decrease';

                } else {

                    $('#adjustment_type').val('increase');
                    type = 'increase';

                }
                $.ajax({

                    method: 'get',

                    // Module endpoint. The core one sits behind the Stock Adjustment
                    // sidebar setting and the 'access_account' subscription, so it
                    // was refused for this business - "this module has been disabled
                    // from manage side bar" - and the Type dropdown could not load
                    // its accounts.
                    url: "/petro-general/dip-reset/inventory-adjustment-account",

                    data: {
                        type
                    },

                    contentType: 'html',

                    success: function(result) {

                        $('#inventory_adjustment_account').empty().append(result);

                    },

                });


                $('#qty_to_adjust').val(qty_to_adjust.toFixed(quantity_presicion));

            })



            $(document).on('click', '.add_new_dip_reading_btn', function() {

                var $btn = $(this);
                if ($btn.data('is_submitting')) {
                    return;
                }
                $btn.data('is_submitting', true).prop('disabled', true);

                var tankId = [];
                var dipReading = [];
                var manualDipReading = [];
                var fuelBalanceDipReading = [];
                var currentQty = [];
                var systemCurrentQty = [];
                var dipDifference = [];
                var note = [];

                /*
                 |------------------------------------------------------------------
                 | Collect EVERY row, and every field each row carries.
                 |------------------------------------------------------------------
                 |
                 | manual_dip_readings[] was not collected at all, so the typed Dip
                 | Reading never reached the server and was stored as 0 on every row.
                 |
                 | Each value is defaulted rather than pushed raw. A row missing one
                 | hidden input used to push `undefined`, and jQuery DROPS undefined
                 | entries when it serialises an array - so the arrays arrived at
                 | different lengths. The controller walks them by index, so a short
                 | array meant the wrong value landed against the wrong tank, or the
                 | save failed outright. That is why one row worked and several did
                 | not.
                 */
                function pgRowValue(row, name) {
                    var v = row.find('input[name="' + name + '"]').val();
                    return (v === undefined || v === null || v === '') ? '0' : v;
                }

                $('#tank_table tbody tr').each(function() {
                    var row = $(this);

                    // Skip any placeholder row that carries no tank.
                    var thisTankId = row.find('input[name="tank_ids[]"]').val();
                    if (!thisTankId) {
                        return;
                    }

                    tankId.push(thisTankId);
                    dipReading.push(pgRowValue(row, 'dip_readings[]'));
                    manualDipReading.push(pgRowValue(row, 'manual_dip_readings[]'));
                    fuelBalanceDipReading.push(pgRowValue(row, 'fuel_balance_dip_readings[]'));
                    currentQty.push(pgRowValue(row, 'current_qtys[]'));
                    systemCurrentQty.push(pgRowValue(row, 'system_current_qtys[]'));
                    dipDifference.push(pgRowValue(row, 'dip_differences[]'));

                    // A note may legitimately be empty - keep it as a blank string.
                    var thisNote = row.find('input[name="notes[]"]').val();
                    note.push(thisNote === undefined || thisNote === null ? '' : thisNote);
                });

                if (tankId.length === 0) {
                    toastr.error('Please add at least one tank before saving.');
                    $btn.data('is_submitting', false).prop('disabled', false);
                    return;
                }

                var data = {

                    location_id: $('#location_id').val(),

                    date_and_time: $('input[name=date_and_time]').val(),

                    tank_manufacturer: $('input[name=tank_manufacturer]').val(),

                    tank_capacity: $('input[name=tank_capacity]').val(),

                    tank_id: tankId,

                    ref_number: $('input[name=ref_number]').val(),

                    dip_reading: dipReading,

                    /*
                     | Sent under the PLURAL names the controller reads first
                     | ($request->manual_dip_readings, ->current_qtys and so on).
                     | The singular keys are kept alongside them so nothing that
                     | still expects the old shape breaks.
                     */
                    manual_dip_readings: manualDipReading,

                    system_current_qtys: systemCurrentQty,

                    dip_differences: dipDifference,

                    fuel_balance_dip_reading: fuelBalanceDipReading,

                    current_qty: currentQty,

                    daily_report_date: $('input[name=daily_report_date]').val(),

                    note: note,

                };

                $.ajax({

                    method: 'post',

                    url: '/petro-general/save-new-dip-reading',

                    data: data,

                    success: function(result) {

                        if (result.success == 1) {

                            toastr.success(result.msg);

                            $('.dip_modal').modal('hide');

                            $('.dip_modal').empty();

                            // Keep disabled after successful save
                            $btn.data('is_submitting', true).prop('disabled', true);

                        } else {

                            toastr.error(result.msg);

                            // Allow retry
                            $btn.data('is_submitting', false).prop('disabled', false);

                        }

                        dip_report_table.ajax.reload();

                    },
                    error: function(xhr, status, error) {
                        // Allow retry on request failure
                        $btn.data('is_submitting', false).prop('disabled', false);

                        /*
                         | Show what actually failed. A silent failure here is what
                         | made the multi-tank save look like the button was dead.
                         */
                        var serverMsg = '';
                        try {
                            serverMsg = (xhr.responseJSON && xhr.responseJSON.msg)
                                ? xhr.responseJSON.msg
                                : (xhr.responseJSON && xhr.responseJSON.message)
                                    ? xhr.responseJSON.message
                                    : '';
                        } catch (e) {
                            serverMsg = '';
                        }

                        toastr.error(serverMsg || ('Could not save the dip (' + xhr.status + ').'));
                        
                        // Show actual error message
                        var errorMsg = 'Server Error: ' + xhr.status + ' - ' + error;
                        if (xhr.responseText) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.msg) {
                                    errorMsg = response.msg;
                                }
                            } catch(e) {
                                errorMsg = xhr.responseText.substring(0, 200);
                            }
                        }
                        toastr.error(errorMsg);
                        console.error('Save Error:', xhr.responseText);
                    }

                });

            });

            // Reset button state when modal closes
            $(document).on('hidden.bs.modal', '.dip_modal', function() {
                $(this).find('.add_new_dip_reading_btn').data('is_submitting', false).prop('disabled', false);

                /*
                 | Empty the modal on close.
                 |
                 | Without this the previous form stays in the DOM. Reopening
                 | .load()s a second copy on top, so the page ends up holding two
                 | sets of the same ids - #add_dip_tank_id, #current_qty and the
                 | rest. jQuery then writes to whichever it finds first, which is
                 | the STALE one, so the visible fields never update: that is why
                 | System Current Qty appeared not to load.
                 |
                 | It is also why opening and closing felt rough - each cycle left
                 | another hidden form and another select2 behind.
                 |
                 | Any select2 inside is destroyed first. select2 attaches its own
                 | markup and handlers outside the element, and simply emptying
                 | the container would orphan them.
                 */
                $(this).find('.select2').each(function() {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });

                $(this).empty();
            });

            if ($('#resetting_date_range').length == 1) {

                $('#resetting_date_range').daterangepicker(dateRangeSettings, function(start, end) {

                    $('#resetting_date_range').val(

                        start.format(moment_date_format) + ' - ' + end.format(moment_date_format)

                    );
                    dip_resetting_table.ajax.reload();

                });

                $('#resetting_date_range').on('cancel.daterangepicker', function(ev, picker) {

                    $('#product_sr_date_filter').val('');

                });

                $('#resetting_date_range')

                    .data('daterangepicker')

                    .setStartDate(moment().startOf('month'));

                $('#resetting_date_range')

                    .data('daterangepicker')

                    .setEndDate(moment().endOf('month'));

            }

            let resetDateRange = $('#resetting_date_range').val();
            if (resetDateRange && resetDateRange.includes(' - ')) {
                let reset_date = resetDateRange.split(' - ');
                $('.resetting_from_date').text(reset_date[0]);
                $('.resetting_to_date').text(reset_date[1]);
            }

            $(document).on('submit', '#edit_new_dip_form', function(e) {
                e.preventDefault(); // Prevent the default form submission

                // Get the form data
                var formData = $(this).serialize();

                // Send the Ajax request

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'post',
                    data: formData,
                    success: function(result) {
                        if (result.success == 1) {
                            toastr.success(result.msg);

                            $('.dip_modal').modal('hide');

                            $('.dip_modal').empty();

                        } else {
                            toastr.error(result.msg);
                        }
                        dip_report_table.ajax.reload();
                    }
                });
            });

            $(document).on('submit', '#dip_chart_add_form,#dip_chart_edit_form,#add_dip_chart_reading_form',
                function(e) {
                    console.log('here');
                    e.preventDefault(); // Prevent the default form submission

                    // Get the form data
                    var formData = $(this).serialize();

                    // Send the Ajax request

                    $.ajax({
                        url: $(this).attr('action'),
                        method: 'post',
                        data: formData,
                        success: function(result) {
                            if (result.success == 1) {
                                toastr.success(result.msg);

                                $('.modal').modal('hide');

                                $('.dip_modal').empty();

                            } else {
                                toastr.error(result.msg);
                            }
                            dip_chart_table.ajax.reload();
                        }
                    });
                });

            $(document).on('submit', '.quick_add_dip_chart_add_form', function(e) {
                e.preventDefault(); // Prevent the default form submission

                // Get the form data
                var formData = $(this).serialize();

                // Send the Ajax request

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'post',
                    data: formData,
                    success: function(result) {
                        if (result.success == 1) {
                            toastr.success(result.msg);

                            $('.modal_dip_modal').modal('hide');

                            $('.modal_dip_modal').empty();

                            $("#add_dip_tank_id").trigger('change');
                            $("#edit_dip_tank_id").trigger('change');

                        } else {
                            toastr.error(result.msg);
                        }
                        dip_chart_table.ajax.reload();
                    }
                });
            });




            $(document).on('submit', '#dip_resetting_form', function(e) {
                e.preventDefault()
                var $form = $(this);

                /*
                 * IS2142: send the WHOLE form when it is the multi-tank version.
                 *
                 * This handler builds its payload by naming each field
                 * individually - tank_id, current_qty, current_dip_difference,
                 * reset_new_dip, adjustment_type. Those were the single-tank
                 * fields, and they no longer exist: the form now carries one row
                 * per tank as rows[i][...].
                 *
                 * So every one of those lookups returned undefined, no rows[]
                 * was sent at all, and the controller received a request with
                 * nothing to save. The page did nothing and said nothing - which
                 * is exactly the report.
                 *
                 * When the tank table is present the entire form is serialized
                 * instead, so every row reaches the server. The hand-built
                 * payload below is kept for any older form that still has those
                 * single-tank fields.
                 */
                if ($form.find('#pg_dip_reset_tank_rows').length) {
                    $.ajax({
                        method: 'post',
                        url: '/petro-general/save-resetting-dip',
                        data: $form.serialize(),
                        success: function (result) {
                            // Same close/refresh the single-tank branch uses.
                            if (result && result.success == 1) {
                                toastr.success(result.msg);
                                $('.dip_modal').modal('hide');
                                $('.dip_modal').empty();
                            } else {
                                toastr.error((result && result.msg) ? result.msg : 'Could not save the dip reset.');
                            }

                            if (typeof dip_resetting_table !== 'undefined') {
                                dip_resetting_table.ajax.reload();
                            }
                        },
                        error: function (xhr) {
                            // Say what failed rather than fail silently.
                            var msg = 'Could not save the dip reset.';

                            if (xhr && xhr.responseJSON && xhr.responseJSON.msg) {
                                msg = xhr.responseJSON.msg;
                            } else if (xhr && xhr.status) {
                                msg = 'Save failed (' + xhr.status + ').';
                            }

                            toastr.error(msg);
                        }
                    });

                    return false;
                }

                $.ajax({

                    method: 'post',

                    url: '/petro-general/save-resetting-dip',

                    data: {

                        location_id: $form.find('#location_id').val(),

                        tank_id: $form.find('#add_reset_tank_id').val(),

                        inventory_adjustment_account: $form.find('#inventory_adjustment_account').val(),

                        meter_reset_form_no: $form.find('input[name=meter_reset_form_no]').val(),

                        date_and_time: $form.find('input[name=date_and_time]').val(),

                        transaction_date: $form.find('input[name=transaction_date]').val(),

                        current_qty: $form.find('input[name=current_qty]').val(),

                        current_dip_difference: $form.find('input[name=current_dip_difference]').val(),

                        reset_new_dip: $form.find('input[name=reset_new_dip]').val(),

                        adjustment_type: $form.find('#adjustment_type').val(),

                        reason: $form.find('#reason').val(),

                    },

                    success: function(result) {

                        if (result.success == 1) {

                            toastr.success(result.msg);

                            $('.dip_modal').modal('hide');

                            $('.dip_modal').empty();

                        } else {

                            toastr.error(result.msg);

                        }

                        dip_resetting_table.ajax.reload();

                    }

                });
                return false;

            });



            $(document).on('change', '#adjustment_type', function() {

                if ($(this).val() === 'increase') {

                    type = 'increase';

                }

                if ($(this).val() === 'decrease') {

                    type = 'decrease';

                }



                $.ajax({

                    method: 'get',

                    // Module endpoint. The core one sits behind the Stock Adjustment
                    // sidebar setting and the 'access_account' subscription, so it
                    // was refused for this business - "this module has been disabled
                    // from manage side bar" - and the Type dropdown could not load
                    // its accounts.
                    url: "/petro-general/dip-reset/inventory-adjustment-account",

                    data: {
                        type
                    },

                    contentType: 'html',

                    success: function(result) {

                        $('#inventory_adjustment_account').empty().append(result);

                    },

                });

            })



            var resetting_columns = [

                {
                    data: 'meter_reset_form_no',
                    name: 'meter_reset_form_no'
                },

                {
                    data: 'date_and_time',
                    name: 'date_and_time'
                },

                {
                    data: 'location_name',
                    name: 'location_name'
                },

                {
                    data: 'tank_name',
                    name: 'tank_name'
                },

                {
                    data: 'product_name',
                    name: 'product_name'
                },

                {
                    data: 'current_qty',
                    name: 'current_qty'
                },

                {
                    data: 'current_dip_difference',
                    name: 'current_dip_difference'
                },

                {
                    data: 'reset_new_dip',
                    name: 'reset_new_dip'
                },

                {
                    data: 'reason',
                    name: 'reason'
                }

            ];



            dip_resetting_table = $('#dip_resetting_table').DataTable({

                processing: true,

                serverSide: true,

                aaSorting: [
                    [0, 'desc']
                ],

                ajax: {

                    url: '{{ action('\Modules\PetroGeneral\Http\Controllers\DipManagementController@getDipResetting') }}',

                    data: function(d) {

                        d.location_id = $('select#resetting_location_id').val();

                        d.tank_id = $('select#resetting_tank_id').val();

                        d.product_id = $('select#resetting_product_id').val();

                        d.start_date = $('input#resetting_date_range')

                            .data('daterangepicker')

                            .startDate.format('YYYY-MM-DD');

                        d.end_date = $('input#resetting_date_range')

                            .data('daterangepicker')

                            .endDate.format('YYYY-MM-DD');

                    },

                },

                columnDefs: [{

                    "targets": 0,

                    // Before
                    // "orderable": false,

                    // After
                    "orderable": true,

                    "searchable": false

                }],

                columns: resetting_columns,

                fnDrawCallback: function(oSettings) {

                },

            });

            dip_chart_table = $('#dip_chart_table').DataTable({

                processing: true,

                serverSide: true,

                ajax: {

                    url: '{{ action('\Modules\PetroGeneral\Http\Controllers\DipManagementController@getDipChart') }}',

                    data: function(d) {

                        d.tank_id = $('select#chart_tank_id').val();

                    },

                },

                columns: [

                    {
                        data: 'date',
                        name: 'date'
                    },

                    {
                        data: 'sheet_name',
                        name: 'sheet_name'
                    },

                    {
                        data: 'fuel_tank_number',
                        name: 'fuel_tanks.fuel_tank_number'
                    },

                    {
                        data: 'tank_manufacturer',
                        name: 'fuel_tanks.tank_manufacturer'
                    },

                    {
                        data: 'tank_manufacturer_phone',
                        name: 'fuel_tanks.tank_manufacturer_phone'
                    },

                    {
                        data: 'storage_volume',
                        name: 'fuel_tanks.storage_volume'
                    },

                    {
                        data: 'dip_reading',
                        name: 'dip_chart_details.dip_reading'
                    },

                    {
                        data: 'dip_reading_value',
                        name: 'dip_chart_details.dip_reading_value'
                    },

                    {
                        data: 'username',
                        name: 'users.username'
                    },

                    {
                        data: 'action',
                        name: 'action',
                        searchable: false
                    }

                ],

                fnDrawCallback: function(oSettings) {

                },

            });

            $('#chart_tank_id').change(function() {

                dip_chart_table.ajax.reload();
            });

            $('#resetting_location_id, #resetting_tank_id , #resetting_product_id, #resetting_date_range').change(
                function() {

                    dip_resetting_table.ajax.reload();



                    if ($('#resetting_location_id').val() !== '' && $('#resetting_location_id').val() !==
                        undefined) {

                        $('.resetting_location_name').text($('#resetting_location_id :selected').text())

                    }

                    let resetDateRange = $('#resetting_date_range').val();
                    if (resetDateRange && resetDateRange.includes(' - ')) {
                        let reset_date = resetDateRange.split(' - ');
                        $('.resetting_from_date').text(reset_date[0]);
                        $('.resetting_to_date').text(reset_date[1]);
                    }

                });
        });

        $(document).on('click', '.view-note-btn', function() {
            var note = $(this).data('note');
            if (note) {
                // Create and show modal
                var modalHtml = '<div class="modal fade" id="view_note_modal" tabindex="-1" role="dialog">' +
                    '<div class="modal-dialog" role="document">' +
                    '<div class="modal-content">' +
                    '<div class="modal-header">' +
                    '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span></button>' +
                    '<h4 class="modal-title">@lang("petrogeneral::lang.note")</h4>' +
                    '</div>' +
                    '<div class="modal-body">' +
                    '<div class="form-group">' +
                    '<textarea class="form-control" rows="5" readonly style="resize: none; white-space: pre-wrap;">' + 
                    $('<div>').text(note).html() + 
                    '</textarea>' +
                    '</div>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-default" data-dismiss="modal">@lang("messages.close")</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                
                $('#view_note_modal').remove();
                
                $('body').append(modalHtml);
                $('#view_note_modal').modal('show');
                
                $('#view_note_modal').on('hidden.bs.modal', function() {
                    $(this).remove();
                });
            }
        });
    </script>

@endsection
