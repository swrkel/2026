@extends('layouts.' . $layout)
@section('title', __('petropd::lang.pump_operators'))

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('petropd::lang.pumper_day_entries') <br>
            {{-- <span class="text-red">{{$pump_operator->name}}</span> --}}
            <span class="text-red">{{ $pump_operator->name ?? '' }}</span>
        </h1>
        <h2 style="color:red;">Shift NO: {{ $shift_number }}</h2>
        <a href="{{ action('Auth\PumpOperatorLoginController@logout') }}" class="btn btn-flat btn-lg pull-right"
            style=" background-color: orange; color: #fff; margin-left: 5px;">@lang('petropd::lang.logout')</a>
        <a href="{{ action('\Modules\PetroPD\Http\Controllers\PumpOperatorController@dashboard') }}"
            class="btn btn-flat btn-lg pull-right"
            style="color: #fff; background-color:#810040; margin-left: 5px;">@lang('petropd::lang.dashboard')
        </a>
        {{-- <a data-href="{{ route('petropd.payment-summary.dashboard', ['only_pumper' => true]) }}"
            class="btn btn-flat btn-lg pull-right btn-modal" data-container=".view_modal"
            style="color: #fff; background-color:#71b306;">@lang('petropd::lang.payment_summary')
        </a> --}}

    </section>
    <div class="clearfix"></div>
    @include('petropd::pd_operators.partials.pumper_day_entries')


@endsection
@section('javascript')
    {{-- PetroPD standalone: avoid loading Petro module JS here. --}}

    <script type="text/javascript">
        var body = document.getElementsByTagName("body")[0];
        body.className += " sidebar-collapse";
        if ($('#date_range').length == 1) {
            $('#date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#date_range').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
            });
            $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#date_range').val('');
            });
            $('#date_range')
                .data('daterangepicker')
                .setStartDate(moment().startOf('month'));
            $('#date_range')
                .data('daterangepicker')
                .setEndDate(moment().endOf('month'));
        }

        $(document).ready(function() {
            console.log('heroooo');
            $.ajaxSetup({
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            function sum_day_entry_payment_amount(api) {
                var total = 0;

                api.rows({
                        page: 'current'
                    })
                    .data()
                    .each(function(row) {
                        total += parseFloat(row.payment_amount_for_total) || 0;
                    });

                return total;
            }

            pump_operators_day_entries_table = $('#pump_operators_day_entries_table').DataTable({
                processing: true,
                serverSide: false,
                deferRender: true,
                ordering: false,
                ajax: {
                    url: "{{ route('petropd.day_entries.index', ['only_pumper' => $only_pumper]) }}",
                    data: function(d) {
                        @if (empty(auth()->user()->pump_operator_id))
                            // d.start_date = $('input#date_range')
                            //     .data('daterangepicker')
                            //     .startDate.format('YYYY-MM-DD');
                            // d.end_date = $('input#date_range')
                            //     .data('daterangepicker')
                            //     .endDate.format('YYYY-MM-DD');
                            // d.location_id = $('#day_entries_location_id').val();
                            // d.pump_operator_id = $('#day_entries_pump_operators').val();
                            // d.pump_id = $('#day_entries_pumps').val();
                            // d.payment_method = $('#day_entries_payment_method').val();
                            // d.difference = $('#day_entries_difference').val();
                        @endif

                        d.shift_id = $("#day_entries_shift_id").val();
                    },
                    error: function(xhr) {
                        var message = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'Unable to load Pumper Day Entries data.';
                        console.error('Pumper Day Entries Ajax error', xhr.responseText || xhr);
                        toastr.error(message);
                    }
                },
                columnDefs: [{
                        "targets": 0,
                        "orderable": false,
                        "searchable": false
                    },
                ],
                columns: [{
                        data: 'action'
                    },
                    {
                        data: 'date'
                    },
                    {
                        data: 'location_name'
                    },
                    @if (empty(auth()->user()->pump_operator_id))
                        {
                            data: 'settlement_no'
                        },
                    @endif {
                        data: 'name'
                    },
                    {
                        data: 'shift_number'
                    },
                    {
                        data: 'pump'
                    },
                    {
                        data: 'starting_meter'
                    },
                    {
                        data: 'closing_meter'
                    },
                    {
                        data: 'testing_ltr'
                    },
                    {
                        data: 'sold_ltr'
                    },
                    {
                        data: 'amount'
                    },
                    {
                        data: 'short_amount'
                    },
                ],
                fnDrawCallback: function(oSettings) {
                    var api = this.api();
                    var sold_ltr = sum_table_col($('#pump_operators_day_entries_table'), 'sold_ltr');
                    $('#footer_sold_ltr').text(sold_ltr);
                    var payment_amount = sum_day_entry_payment_amount(api);
                    $('#footer_sold_amount').text(payment_amount);


                    __currency_convert_recursively($('#pump_operators_day_entries_table'));
                },
            });

            console.log('hiiii');

            $('#day_entries_location_id, #day_entries_pump_operator, #day_entries_pump_operator, #day_entries_payment_method, #day_entries_date_range, #day_entries_difference, #day_entries_shift_id')
                .change(function() {
                    pump_operators_day_entries_table.ajax.reload();
                    reloadDayEntries();
                });

            reloadDayEntries();
        });

        function reloadDayEntries() {
            $("#pumper_day_entry_summary").empty();

            console.log('reloadDayEntries');

            $.ajax({
                method: 'GET',
                url: '{{ route('petropd.day_entries.summary') }}',
                dataType: 'html',
                data: {
                    'shift_id': $("#day_entries_shift_id").val(),
                    'only_pumper': 1
                },
                success: function(result) {
                    $("#pumper_day_entry_summary").html(result);
                },
            });
        }
    </script>
@endsection
