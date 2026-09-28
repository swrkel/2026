@extends('layouts.' . $layout)
@section('title', __('pumperdashboard::lang.daily_pump_status'))

@section('content')
    @include('pumperdashboard::partials.pumper_dashboard_ui_standard')
    <section class="content-header pumper-ui-standard">
        <div class="col-md-12">
            <h1 class="pull-left">@lang('pumperdashboard::lang.daily_pump_status')</h1>
            <h2 class="text-red pull-right">@lang('pumperdashboard::lang.date') : {{ @format_date(date('Y-m-d')) }}</h2>
            <h2 style="color: red; text-align: center;">Shift_NO: {{ $shift_number }}</h2>
        </div>
    </section>
    @include('pumperdashboard::.partials.daily_pump_status')

    <div class="modal fade pump_operator_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

@endsection


@section('javascript')
    <script type="text/javascript">
        //     $(document).ready( function(){
        //     list_daily_collection_table = $('#list_daily_collection_table').DataTable({
        //         processing: true,
        //         serverSide: true,
        //         aaSorting: [[0, 'desc']],
        //         ajax: {
        //             url: '{{ action('\Modules\PumperDashboard\Http\Controllers\PumperDayEntryController@getDailyCollection') }}?only_pumper=yes',
        //             data: function(d) {

        //             },
        //         },
        //         columnDefs: [ {
        //             "targets": 0,
        //             "orderable": false,
        //             "searchable": false
        //         }
        //         ],
        //         columns: [
        //             { data: 'action', searchable: false, orderable: false },
        //             { data: 'date_and_time', name: 'date_and_time' },
        //             { data: 'location_name', name: 'business_locations.name' },
        //             { data: 'name', name: 'name' },
        //             { data: 'pump_no', name: 'pump_no' },
        //             { data: 'starting_meter', name: 'starting_meter' },
        //             { data: 'closing_meter', name: 'closing_meter' },
        //             { data: 'sold_ltr', name: 'sold_ltr' },
        //             { data: 'testing_ltr', name: 'testing_ltr' },
        //             { data: 'sold_amount', name: 'sold_amount' },
        //         ],
        //         fnDrawCallback: function(oSettings) {
        //             __currency_convert_recursively($('#list_daily_collection_table'));
        //         },
        //     });
        // });



        // $(document).on('click', 'a.delete_daily_collection', function(e) {
        // 		var page_details = $(this).closest('div.page_details')
        // 		e.preventDefault();
        //         swal({
        //             title: LANG.sure,
        //             icon: 'warning',
        //             buttons: true,
        //             dangerMode: true,
        //         }).then(willDelete => {
        //             if (willDelete) {
        //                 var href = $(this).attr('href');
        //                 var data = $(this).serialize();
        //                 $.ajax({
        //                     method: 'DELETE',
        //                     url: href,
        //                     dataType: 'json',
        //                     data: data,
        //                     success: function(result) {
        //                         if (result.success == true) {
        //                             toastr.success(result.msg);
        //                         } else {
        //                             toastr.error(result.msg);
        //                         }
        //                         list_daily_collection_table.ajax.reload();
        //                     },
        //                 });
        //             }
        //         });
        //     });
    </script>

    <script type="text/javascript">
        $(document).ready(function() {
            var $dailyCollection = $('#list_daily_collection_table');
            if (!$dailyCollection.length) {
                return;
            }
            if ($.fn.DataTable.isDataTable($dailyCollection)) {
                list_daily_collection_table = $dailyCollection.DataTable();
                return;
            }
            list_daily_collection_table = $dailyCollection.DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ action('\Modules\PumperDashboard\Http\Controllers\PumperDayEntryController@getDailyCollection') }}?only_pumper=yes',
                    data: function(d) {
                        // Add any additional data here if needed
                    },
                },
                columnDefs: [{
                    "targets": 0,
                    "orderable": false,
                    "searchable": false,
                    "defaultContent": '-'
                }],
                columns: [{
                        data: 'date_and_time',
                        name: 'date_and_time'
                    },
                    {
                        data: 'location_name',
                        name: 'business_locations.name'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'shift_number',
                        name: 'shift_number'
                    },
                    {
                        data: 'pump_no',
                        name: 'pump_no'
                    },
                    {
                        data: 'starting_meter',
                        name: 'starting_meter',
                        render: function(data) {
                            return data ? parseFloat(data).toFixed(3) : '0.000';
                        }
                    },
                    {
                        data: 'closing_meter',
                        name: 'closing_meter',
                        render: function(data) {
                            return data ? parseFloat(data).toFixed(3) : '0.000';
                        }
                    },
                    {
                        data: 'shift_closed',
                        name: 'shift_closed'
                    },
                    {
                        data: 'sold_ltr',
                        name: 'sold_ltr'
                    },
                    {
                        data: 'testing_ltr',
                        name: 'testing_ltr'
                    },
                    {
                        data: 'sold_amount',
                        name: 'sold_amount'
                    },
                ],
                fnDrawCallback: function(oSettings) {
                    __currency_convert_recursively($('#list_daily_collection_table'));
                    var total_sold_amount = sum_table_col($('#list_daily_collection_table'),
                        'sold_amount');
                    $('#dc_footer_sold_fuel_amount').text(__number_f(total_sold_amount, false, false,
                        __quantity_precision));
                    // Update footer with total values
                    var api = this.api();
                    $(api.column(8).footer()).html(api.ajax.json().total_sold_ltr);
                    $(api.column(9).footer()).html(api.ajax.json().total_testing_ltr);
                    // $(api.column(10).footer()).html(api.ajax.json().total_sold_amount);
                },
            });
        });

        // Delete functionality remains the same
        $(document).on('click', 'a.delete_daily_collection', function(e) {
            var page_details = $(this).closest('div.page_details');
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
                            list_daily_collection_table.ajax.reload();
                        },
                    });
                }
            });
        });
    </script>

@endsection




