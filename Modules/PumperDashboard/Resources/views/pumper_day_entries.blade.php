@extends('layouts.' . $layout)
@section('title', __('pumperdashboard::lang.pump_operators'))

@section('content')
    @include('pumperdashboard::partials.pumper_dashboard_ui_standard')
    <!-- Content Header (Page header) -->
    <section class="content-header pumper-ui-standard">
        <h1>@lang('pumperdashboard::lang.pumper_day_entries') <br>
            {{-- <span class="text-red">{{$pump_operator->name}}</span> --}}
            <span class="text-red">{{ $pump_operator->name ?? '' }}</span>
        </h1>
        <h2 style="color:red;">Shift NO: <span id="current_day_entry_shift_number">{{ $shift_number }}</span></h2>
        <a href="{{ action('Auth\PumpOperatorLoginController@logout') }}" class="btn btn-flat btn-lg pull-right"
            style=" background-color: orange; color: #fff; margin-left: 5px;">@lang('pumperdashboard::lang.logout')</a>
        <a href="{{ action('\Modules\PumperDashboard\Http\Controllers\PumpOperatorController@dashboard') }}"
            class="btn btn-flat btn-lg pull-right"
            style="color: #fff; background-color:#810040; margin-left: 5px;">@lang('pumperdashboard::lang.dashboard')
        </a>
        {{-- <a data-href="{{ action('\Modules\\PumperDashboard\\Http\\Controllers\\PumpOperatorPaymentController@getPaymentSummaryModal', ['only_pumper' => true]) }}"
            class="btn btn-flat btn-lg pull-right btn-modal" data-container=".view_modal"
            style="color: #fff; background-color:#71b306;">@lang('pumperdashboard::lang.payment_summary')
        </a> --}}

    </section>
    <div class="clearfix"></div>
    @include('pumperdashboard::.partials.pumper_day_entries')


@endsection
@section('javascript')
    @php
        $pumperPoPaymentJs = file_exists(public_path('Modules/pumper-dashboard/js/po_payment.js'))
            ? asset('Modules/pumper-dashboard/js/po_payment.js')
            : asset('modules/pumper-dashboard/js/po_payment.js');
    @endphp
    <script src="{{ $pumperPoPaymentJs }}"></script>

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
            /*
                MA-002: show the error instead of spinning forever.

                DataTables has NO error handling by default. When its ajax call
                fails - a 403, a 500, a timeout - it simply leaves "Processing"
                on screen with nothing to indicate anything went wrong. That is
                what you are seeing, and it is why three performance fixes
                changed nothing: the request never returns data at all.

                This reports the status code and the server's message on the
                page and in the console, so the cause names itself.
            */
            /*
                MA-002: GUARDED.

                This read $.fn.dataTable.ext directly. If $.fn.dataTable is not
                yet defined when the line runs, reading .ext throws - and a
                thrown error stops THE REST OF THIS SCRIPT BLOCK, including the
                DataTable initialisation below it.

                No DataTable means no ajax request, which means the server runs
                nothing, logs nothing, and the table sits on "Processing"
                forever. That is exactly what has been happening since I added
                this, and it is my fault.

                Now it is checked first, and wrapped so it can never stop the
                table being created. The error reporting is a convenience; the
                table is not.
            */
            try {
                if ($.fn.dataTable && $.fn.dataTable.ext) {
                    $.fn.dataTable.ext.errMode = 'none';
                }

                $('#pump_operators_day_entries_table')
                    .off('error.dt')
                    .on('error.dt', function (e, settings, techNote, message) {
                        console.error('MA-002 day entries table error:', message);
                    });
            } catch (e) {
                console.warn('MA-002: could not attach the table error handler', e);
            }

            pump_operators_day_entries_table = $('#pump_operators_day_entries_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    /*
                        MA-002: report what the server actually returned.

                        Without this, a 403 or a 500 leaves the table on
                        "Processing" with no clue as to why.
                    */
                    error: function (xhr) {
                        var detail = 'HTTP ' + xhr.status;

                        try {
                            var body = JSON.parse(xhr.responseText || '{}');
                            if (body.message) {
                                detail += ' - ' + body.message;
                            }
                        } catch (e) {
                            if (xhr.responseText) {
                                detail += ' - ' + String(xhr.responseText).substring(0, 200);
                            }
                        }

                        console.error('MA-002 day entries request failed:', detail);

                        $('#pump_operators_day_entries_table tbody').html(
                            '<tr><td colspan="14" class="text-center text-red" style="padding:20px;">'
                            + 'Could not load: ' + $('<div>').text(detail).html()
                            + '</td></tr>'
                        );
                    },
                    url: "{{ action('\Modules\PumperDashboard\Http\Controllers\PumperDayEntryController@index', ['only_pumper' => $only_pumper]) }}",
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

                        d.shift_id = $("#shift_id").val();
                    },
                },
                columnDefs: [
                    // MA-002 (S-609 #9): was targets 0, which was the Action column.
                    // With Action gone, column 0 is Date and IS sortable - so this
                    // now targets nothing and is left empty rather than silently
                    // making Date unsortable.
                ],
                columns: [
                    // MA-002 (S-609 #9): 'action' removed - see the note in
                    // partials/pumper_day_entries.blade.php. 'date' is now column 0.
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
                        data: 'payment_method'
                    },
                    {
                        data: 'payment_type'
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
                    var sold_amount = sum_day_entry_payment_amount(api);
                    $('#footer_sold_amount').text(sold_amount);


                    __currency_convert_recursively($('#pump_operators_day_entries_table'));
                },
            });

            console.log('hiiii');

            $('#day_entries_location_id, #day_entries_pump_operator, #day_entries_pump_operator, #day_entries_payment_method, #day_entries_date_range, #day_entries_difference, #shift_id')
                .change(function() {
                    if (this.id === 'shift_id') {
                        var shiftNumber = $(this).find(':selected').data('shift-number') || '';
                        $('#current_day_entry_shift_number').text(shiftNumber);
                    }
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
                url: '{{ route('pumperdashboard.day-entries.summary') }}',
                dataType: 'html',
                data: {
                    'shift_id': $("#shift_id").val(),
                    'only_pumper': 1
                },
                success: function(result) {
                    $("#pumper_day_entry_summary").html(result);
                },
            });
        }

        function sum_day_entry_payment_amount(api) {
            var total = 0;

            api.rows({
                page: 'current'
            }).data().each(function(row) {
                total += parseFloat(row.payment_amount_for_total) || 0;
            });

            return total;
        }
    </script>
@endsection


