@extends('layouts.' . $layout)
@section('title', __('petro::lang.payment_summary'))
<style>
    #pump_operators_payment_summary_table td,
    #pump_operators_payment_summary_table th {
        padding: 5px 8px;
        /* reduce default padding */
        white-space: nowrap;
        /* avoid wrapping if needed */
    }
</style>
@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">

    </section>
    <div class="clearfix">

    </div>
    @include('realtimeentries::partials.payment_summary')


@endsection
@section('javascript')
    <script type="text/javascript">
        var body = document.getElementsByTagName("body")[0];
        body.className += " sidebar-collapse";

        if ($('#payment_summary_date_range').length == 1) {
            $('#payment_summary_date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#payment_summary_date_range').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
            });
            $('#payment_summary_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#payment_summary_date_range').val('');
            });
            $('#payment_summary_date_range')
                .data('daterangepicker')
                .setStartDate(moment().startOf('month'));
            $('#payment_summary_date_range')
                .data('daterangepicker')
                .setEndDate(moment().endOf('month'));
        }
        $(document).ready(function() {
            pump_operators_payment_summary_table = $('#pump_operators_payment_summary_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [
                    [0, 'desc']
                ],
                ajax: {
                    url: "{{ action('\Modules\RealTimeEntries\Http\Controllers\RealTimeEntriesController@paymentSummary', ['only_pumper' => true]) }}",
                    data: function(d) {
                        d.shift_id = $("#payment_summary_shift_id").val();
                        d.location_id = $("#payment_summary_location_id").val();
                        d.payment_method = $("#payment_summary_payment_method").val();
                        d.pump_operator_id = $("#payment_summary_pump_operators").val();
                        d.customer_id = $("#payment_summary_customer").val();
                        d.slip_no = $("#payment_summary_slip_no").val();
                        d.order_no = $("#payment_summary_order_no").val();
                    },
                },
                autoWidth: false, // <-- important
                columnDefs: [{
                    "targets": 0,
                    "orderable": false,
                    "searchable": false
                }],
                columns: [{
                        data: 'action',
                        name: 'action'
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'location_name',
                        name: 'business_locations.name'
                    },
                    {
                        data: 'time',
                        name: 'time'
                    },
                    {
                        data: 'pump_operator_name',
                        name: 'pump_operators.name'
                    },
                    {
                        data: 'shift_number',
                        name: 'shift_number'
                    },
                    {
                        data: 'collection_form_no',
                        name: 'collection_form_no'
                    },
                    {
                        data: 'payment_type',
                        name: 'payment_type'
                    },
                    {
                        data: 'customer_name',
                        name: 'customer_name'
                    },
                    {
                        data: 'slip_no',
                        name: 'slip_no'
                    },
                    {
                        data: 'order_no',
                        name: 'order_no'
                    },
                    {
                        data: 'cheque_no',
                        name: 'cheque_no'
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    },
                    @if (empty($only_pumper)) { data: 'note', name: 'note' },
                    { data: 'edited_by', name: 'edited_by' }, @endif
                ],
                fnDrawCallback: function(oSettings) {
                    var footer_payment_summary_amount = sum_table_col($(
                        '#pump_operators_payment_summary_table'), 'amount');
                    $('#footer_payment_summary_amount').text(footer_payment_summary_amount);

                    __currency_convert_recursively($('#pump_operators_payment_summary_table'));
                },
            });

            $(document).on('change', '#payment_summary_shift_id', function() {
                pump_operators_payment_summary_table.ajax.reload();
            });
        });
        $(document).on('change', '.select2', function() {
            pump_operators_payment_summary_table.ajax.reload();
        });

        $(document).on('keyup', '#payment_summary_slip_no, #payment_summary_order_no', function() {
            pump_operators_payment_summary_table.ajax.reload();
        });
    </script>
@endsection
