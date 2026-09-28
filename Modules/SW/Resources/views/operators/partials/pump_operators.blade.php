@include('sw::partials.tab_styles')

@include('sw::operators.partials._dropdown_fix')

@push('css')
<style>
#sw_pump_operators_table { width: 100% !important; }
</style>
@endpush

{{--
    Pump Operators tab.

    Follows PetroGeneral's pump_operators partial: a filter row in
    components.filters, then a datatable inside components.widget with a totals
    footer.

    Reads the business's own pump_operators table - operators are the
    business's people, not this module's records, so SW shares them with
    everything else rather than keeping a second list that would drift.
--}}

<section class="content">

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_op_location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('sw_op_location_id', $business_locations ?? [], $default_location ?? null, [
                            'class' => 'form-control select2',
                            'id' => 'sw_op_location_id',
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_op_operator', __('sw::lang.pump_operator') . ':') !!}
                        {!! Form::select('sw_op_operator', $operator_list ?? [], null, [
                            'class' => 'form-control select2',
                            'id' => 'sw_op_operator',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_op_date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('sw_op_date_range', null, [
                            'class' => 'form-control',
                            'id' => 'sw_op_date_range',
                            'readonly' => true,
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_op_status', __('sw::lang.status') . ':') !!}
                        {!! Form::select('sw_op_status', [
                            'active' => __('sw::lang.active'),
                            'inactive' => __('sw::lang.inactive'),
                        ], null, [
                            'class' => 'form-control select2',
                            'id' => 'sw_op_status',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

            @endcomponent
        </div>
    </div>

    @component('components.widget', [
        'class' => 'box-primary',
        'title' => __('sw::lang.all_pump_operators'),
    ])

        @slot('tool')
            <div class="row">
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-primary btn-modal"
                        data-href="{{ route('sw.operators.create') }}"
                        data-container=".sw_operator_modal">
                        <i class="fa fa-plus"></i> @lang('messages.add')
                    </button>
                </div>
            </div>
        @endslot

        <div class="sw-table-wrap table-responsive">
            <table class="table table-bordered table-striped" id="sw_pump_operators_table" style="width:100%">
                <thead>
                    <tr>
                        <th class="notexport">@lang('messages.action')</th>
                        <th>@lang('sw::lang.pump_operator')</th>
                        <th>@lang('sw::lang.location')</th>
                        <th>@lang('sw::lang.mobile')</th>
                        <th>@lang('sw::lang.commission_type')</th>
                        <th>@lang('sw::lang.commission_rate')</th>
                        <th>@lang('sw::lang.excess_amount')</th>
                        <th>@lang('sw::lang.short_amount')</th>
                        <th>@lang('sw::lang.status')</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="bg-gray font-17 footer-total text-center">
                        <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                        <td><span class="display_currency" id="sw_footer_excess" data-currency_symbol="true"></span></td>
                        <td><span class="display_currency" id="sw_footer_short" data-currency_symbol="true"></span></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    @endcomponent

</section>

@push('javascript')
<script>
$(function () {

    /*
     | Client-side, deliberately.
     |
     | Server-side paging was costing a full round trip per filter change - and
     | on this estate the framework takes about a second to boot before any of
     | this code runs, so every keystroke felt slow. A location has of the order
     | of ten operators, not thousands: fetching them once and filtering in the
     | browser is instant, and the round trip buys nothing.
     |
     | If a business ever runs to thousands of operators this should go back to
     | server-side. The threshold is somewhere in the low thousands, well above
     | anything here.
    */
    var swOperatorStartDate = '';
    var swOperatorEndDate = '';

    var swOperatorsTable = $('#sw_pump_operators_table').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '{{ route('sw.operators.data') }}',
            dataSrc: 'data',
            data: function (d) {
                // Location still goes to the server: it decides WHICH operators
                // are loaded, rather than filtering ones already here.
                d.location_id = $('#sw_op_location_id').val();
                d.start_date = swOperatorStartDate;
                d.end_date = swOperatorEndDate;
            }
        },
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'name', name: 'pump_operators.name' },
            { data: 'location_name', name: 'business_locations.name' },
            { data: 'mobile', name: 'pump_operators.mobile' },
            { data: 'commission_type', name: 'pump_operators.commission_type' },
            { data: 'commission_ap', name: 'pump_operators.commission_ap' },
            { data: 'excess_amount', name: 'pump_operators.excess_amount' },
            { data: 'short_amount', name: 'pump_operators.short_amount' },
            { data: 'status', name: 'pump_operators.active', orderable: false }
        ],
        /*
         | Totals over every FILTERED row, not just the page on screen.
         |
         | { search: 'applied' } is what makes this correct: it counts what the
         | filters have left, across all pages, so the footer agrees with what
         | the user has asked to see.
        */
        footerCallback: function () {
            var api = this.api();

            var sum = function (colIndex) {
                return api
                    .column(colIndex, { search: 'applied' })
                    .data()
                    .reduce(function (a, b) {
                        var n = parseFloat(String(b).replace(/,/g, ''));
                        return a + (isNaN(n) ? 0 : n);
                    }, 0);
            };

            $('#sw_footer_excess').text(sum(6).toFixed(2));
            $('#sw_footer_short').text(sum(7).toFixed(2));
        }
    });

    /*
     | Location reloads, because it decides which operators are fetched at all.
     | Everything else filters what is already here - no request, no waiting.
    */
    $('#sw_op_location_id').on('change', function () {
        swOperatorsTable.ajax.reload();
    });

    $('#sw_op_operator').on('change', function () {
        var val = $(this).val();
        // Column 1 is the operator name; an exact match avoids one operator's
        // name matching another that contains it.
        swOperatorsTable.column(1).search(val ? '^' + swEscapeRegex($('#sw_op_operator option:selected').text()) + '$' : '', true, false).draw();
    });

    $('#sw_op_status').on('change', function () {
        var val = $(this).val();
        swOperatorsTable.column(8).search(val ? swEscapeRegex(val) : '', true, false).draw();
    });

    // S716: Date Range follows the same daterangepicker behaviour used by
    // List SW Shifts. Changing the range reloads only this small operator data
    // set and recalculates the period Excess/Shortage figures server-side.
    if ($.fn.daterangepicker) {
        var swOperatorDateSettings = (typeof dateRangeSettings !== 'undefined')
            ? $.extend(true, {}, dateRangeSettings)
            : {};
        swOperatorDateSettings.autoUpdateInput = false;
        if (!swOperatorDateSettings.locale) swOperatorDateSettings.locale = {};
        swOperatorDateSettings.locale.cancelLabel = swOperatorDateSettings.locale.cancelLabel || 'Clear';

        $('#sw_op_date_range').daterangepicker(swOperatorDateSettings);
        $('#sw_op_date_range').on('apply.daterangepicker', function (ev, picker) {
            var fmt = (typeof moment_date_format !== 'undefined') ? moment_date_format : 'MM/DD/YYYY';
            swOperatorStartDate = picker.startDate.format('YYYY-MM-DD');
            swOperatorEndDate = picker.endDate.format('YYYY-MM-DD');
            $(this).val(picker.startDate.format(fmt) + ' - ' + picker.endDate.format(fmt));
            swOperatorsTable.ajax.reload(null, false);
        });
        $('#sw_op_date_range').on('cancel.daterangepicker', function () {
            swOperatorStartDate = '';
            swOperatorEndDate = '';
            $(this).val('');
            swOperatorsTable.ajax.reload(null, false);
        });
    }

    function swEscapeRegex(str) {
        return String(str).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

});
</script>
@endpush
