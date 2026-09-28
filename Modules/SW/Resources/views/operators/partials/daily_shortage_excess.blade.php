@include('sw::partials.tab_styles')

{{--
    Daily Shortage Excess.

    Where a difference is RECORDED against a shift: "this operator was 500
    short". Entered by hand rather than computed - the settlement is not final
    until the user has added every payment, so a figure worked out now would be
    wrong by the time it mattered.

    Recovering or paying it happens from the operator's row on the Pump
    Operators tab. The Outstanding column here shows what is left.

    When a settlement is prepared it auto-loads these for every shift it covers,
    and totals them.
--}}

<section class="content">

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_se_location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('sw_se_location_id', $business_locations ?? [], $default_location ?? null, [
                            'class' => 'form-control select2', 'id' => 'sw_se_location_id', 'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_se_operator', __('sw::lang.pump_operator') . ':') !!}
                        {!! Form::select('sw_se_operator', $operator_list ?? [], null, [
                            'class' => 'form-control select2', 'id' => 'sw_se_operator',
                            'style' => 'width:100%', 'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_se_type', __('sw::lang.type') . ':') !!}
                        {!! Form::select('sw_se_type', [
                            'shortage' => __('sw::lang.shortage'),
                            'excess' => __('sw::lang.excess'),
                        ], null, [
                            'class' => 'form-control select2', 'id' => 'sw_se_type',
                            'style' => 'width:100%', 'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_se_shift_no', __('sw::lang.shift_no') . ':') !!}
                        {!! Form::text('sw_se_shift_no', null, [
                            'class' => 'form-control', 'id' => 'sw_se_shift_no',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

            @endcomponent
        </div>
    </div>

    @component('components.widget', [
        'class' => 'box-primary',
        'title' => __('sw::lang.all_daily_shortage_excess'),
    ])

        @slot('tool')
            <div class="row">
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-primary btn-modal"
                        data-href="{{ route('sw.shortage-excess.create') }}"
                        data-container=".sw_shortage_excess_modal">
                        <i class="fa fa-plus"></i> @lang('messages.add')
                    </button>
                </div>
            </div>
        @endslot

        <div class="sw-table-wrap table-responsive">
            <table class="table table-bordered table-striped" id="sw_shortage_excess_table" style="width:100%">
                <thead>
                    <tr>
                        <th class="notexport">@lang('messages.action')</th>
                        <th>@lang('sw::lang.date')</th>
                        <th>@lang('sw::lang.sw_shift')</th>
                        <th>@lang('sw::lang.pump_operator')</th>
                        <th>@lang('sw::lang.type')</th>
                        <th class="text-right">@lang('sw::lang.amount')</th>
                        <th class="text-right">@lang('sw::lang.settled')</th>
                        <th class="text-right">@lang('sw::lang.outstanding')</th>
                        <th>@lang('sw::lang.note')</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="bg-gray font-17 footer-total text-center">
                        <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                        <td class="text-right"><span id="sw_se_footer_amount"></span></td>
                        <td class="text-right"><span id="sw_se_footer_settled"></span></td>
                        <td class="text-right"><span id="sw_se_footer_outstanding"></span></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    @endcomponent

    <div class="modal fade sw_shortage_excess_modal" tabindex="-1" role="dialog"></div>

</section>

@include('sw::operators.partials._dropdown_fix')

@push('css')
<style>
#sw_shortage_excess_table { width: 100%% !important; }
</style>
@endpush

@push('javascript')
<script>
$(function () {

    var swSeTable = $('#sw_shortage_excess_table').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '{{ route('sw.shortage-excess.data') }}',
            dataSrc: 'data',
            data: function (d) { d.location_id = $('#sw_se_location_id').val(); }
        },
        order: [[1, 'desc']],
        columns: [
            { data: 'action', orderable: false, searchable: false },
            { data: 'date' },
            { data: 'shift_no' },
            { data: 'operator' },
            { data: 'type' },
            { data: 'amount', className: 'text-right' },
            { data: 'settled', className: 'text-right' },
            { data: 'outstanding', className: 'text-right' },
            { data: 'note' }
        ],
        /*
         | Shortages and excesses are summed SEPARATELY in spirit but shown as
         | one total here, because the type filter is how you look at one or the
         | other. Mixing them in a single figure without filtering would net a
         | shortage against an excess and hide both.
        */
        footerCallback: function () {
            var api = this.api();
            var sum = function (i) {
                return api.column(i, { search: 'applied' }).data().reduce(function (a, b) {
                    var n = parseFloat(String(b).replace(/,/g, ''));
                    return a + (isNaN(n) ? 0 : n);
                }, 0);
            };
            $('#sw_se_footer_amount').text(sum(5).toFixed(2));
            $('#sw_se_footer_settled').text(sum(6).toFixed(2));
            $('#sw_se_footer_outstanding').text(sum(7).toFixed(2));
        }
    });

    $('#sw_se_location_id').on('change', function () { swSeTable.ajax.reload(); });

    $('#sw_se_operator').on('change', function () {
        var name = $('#sw_se_operator option:selected').text();
        swSeTable.column(3).search($(this).val() ? name : '').draw();
    });

    $('#sw_se_type').on('change', function () {
        swSeTable.column(4).search($(this).val() || '').draw();
    });

    $('#sw_se_shift_no').on('input', function () {
        swSeTable.column(2).search($(this).val()).draw();
    });

});
</script>
@endpush
