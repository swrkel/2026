@include('sw::partials.tab_styles')

{{--
    Daily Shift.

    Open a shift, assign operators to it, close it.

    Several shifts can be open at one location at once, so this shows them as a
    list rather than assuming a single current shift. Choosing one opens its
    operator assignment beneath.

    The two-list assignment - pending on the left, assigned on the right - is
    kept from the page your operators already know.
--}}

<section class="content">

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_ds_location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('sw_ds_location_id', $business_locations ?? [], $default_location ?? null, [
                            'class' => 'form-control select2', 'id' => 'sw_ds_location_id', 'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_ds_status', __('sw::lang.status') . ':') !!}
                        {!! Form::select('sw_ds_status', [
                            'open' => __('sw::lang.open'),
                            'closed' => __('sw::lang.closed_status'),
                            'settled' => __('sw::lang.settled_status'),
                        ], 'open', [
                            'class' => 'form-control select2', 'id' => 'sw_ds_status',
                            'style' => 'width:100%', 'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sw_ds_shift_no', __('sw::lang.shift_no') . ':') !!}
                        {!! Form::text('sw_ds_shift_no', null, [
                            'class' => 'form-control', 'id' => 'sw_ds_shift_no',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>

            @endcomponent
        </div>
    </div>

    @component('components.widget', [
        'class' => 'box-primary',
        'title' => __('sw::lang.shifts'),
    ])

        @slot('tool')
            <div class="row">
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-primary btn-modal"
                        data-href="{{ route('sw.shifts.create') }}"
                        data-container=".sw_shift_modal">
                        <i class="fa fa-plus"></i> @lang('sw::lang.open_new_shift')
                    </button>
                </div>
            </div>
        @endslot

        <div class="sw-table-wrap table-responsive">
            <table class="table table-bordered table-striped" id="sw_daily_shift_table" style="width:100%">
                <thead>
                    <tr>
                        <th class="notexport">@lang('messages.action')</th>
                        <th>@lang('sw::lang.shift_no')</th>
                        <th>@lang('sw::lang.date')</th>
                        <th>@lang('sw::lang.location')</th>
                        <th>@lang('sw::lang.operators')</th>
                        <th>@lang('sw::lang.status')</th>
                        <th>@lang('sw::lang.opened_by')</th>
                        <th>@lang('sw::lang.closed_at')</th>
                        <th class="never">Location ID</th>
                        <th class="never">Status Key</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (($daily_shift_rows ?? collect()) as $row)
                        @php
                            try {
                                $swDate = ! empty($row->shift_date)
                                    ? \Carbon\Carbon::parse($row->shift_date)->format('d/m/Y')
                                    : '—';
                            } catch (\Throwable $e) {
                                $swDate = (string) ($row->shift_date ?? '—');
                            }

                            try {
                                $swClosedAt = ! empty($row->closed_at)
                                    ? \Carbon\Carbon::parse($row->closed_at)->format('d/m/Y H:i')
                                    : '—';
                            } catch (\Throwable $e) {
                                $swClosedAt = (string) ($row->closed_at ?? '—');
                            }

                            $swStatus = (int) ($row->effective_status ?? \Modules\SW\Entities\Shift::STATUS_OPEN);
                            $swStatusMap = [
                                \Modules\SW\Entities\Shift::STATUS_OPEN => ['success', __('sw::lang.open')],
                                \Modules\SW\Entities\Shift::STATUS_CLOSED => ['warning', __('sw::lang.closed_status')],
                                \Modules\SW\Entities\Shift::STATUS_SETTLED => ['primary', __('sw::lang.settled_status')],
                                \Modules\SW\Entities\Shift::STATUS_VOID => ['default', __('sw::lang.void')],
                            ];
                            [$swStatusClass, $swStatusText] = $swStatusMap[$swStatus] ?? ['default', '—'];
                            $swOperatorNames = $row->operator_names ?? collect();
                        @endphp
                        <tr>
                            <td>@include('sw::operators.partials.daily_shift_actions', ['row' => $row])</td>
                            <td><strong>{{ $row->sw_shift_no ?? '—' }}</strong></td>
                            <td data-order="{{ $row->shift_date ?? '' }}">{{ $swDate }}</td>
                            <td>{{ $row->location_name ?? '—' }}</td>
                            <td>
                                @if ($swOperatorNames->isEmpty())
                                    <span class="text-muted">@lang('sw::lang.none_assigned')</span>
                                @else
                                    {{ $swOperatorNames->implode(', ') }}
                                @endif
                            </td>
                            <td><span class="label label-{{ $swStatusClass }}">{{ $swStatusText }}</span></td>
                            <td>{{ $row->opened_by ?? '—' }}</td>
                            <td data-order="{{ $row->closed_at ?? '' }}">{{ $swClosedAt }}</td>
                            <td>{{ (int) ($row->location_id ?? 0) }}</td>
                            <td>{{ $row->semantic_status ?? 'unknown' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    @endcomponent

    <div class="modal fade sw_shift_modal" tabindex="-1" role="dialog"></div>

</section>

@include('sw::operators.partials._dropdown_fix')

@push('css')
<style>
#sw_daily_shift_table { width: 100% !important; }
</style>
@endpush

@push('javascript')
<script>
$(function () {

    /*
     | IS2270 - this table is intentionally client-side.
     |
     | The rows arrive with the page, so opening /sw/shifts or
     | /sw/shift-operations does not make a second tenant/auth Ajax request.
     | This removes the repeated DataTables tn/7 failure without bypassing the
     | sw_shifts table: the page controller reads that same authoritative table.
    */
    var swDsTable = $('#sw_daily_shift_table').DataTable({
        processing: false,
        serverSide: false,
        order: [[2, 'desc']],
        columnDefs: [
            { targets: [0], orderable: false, searchable: false },
            { targets: [8, 9], visible: false, searchable: true }
        ]
    });

    function exactSearch(value) {
        if (!value) {
            return '';
        }
        return '^' + $.fn.dataTable.util.escapeRegex(String(value)) + '$';
    }

    function applyShiftFilters() {
        swDsTable.column(8)
            .search(exactSearch($('#sw_ds_location_id').val()), true, false);
        swDsTable.column(9)
            .search(exactSearch($('#sw_ds_status').val()), true, false);
        swDsTable.draw();
    }

    $('#sw_ds_location_id, #sw_ds_status').on('change', applyShiftFilters);

    $('#sw_ds_shift_no').on('input', function () {
        swDsTable.column(1).search($(this).val()).draw();
    });

    // Apply the default location/Open filters on first load.
    applyShiftFilters();

    // Modal saves already redirect in the normal flow. If a host-level modal
    // handler saves through Ajax and emits this event, refresh once so the
    // server-rendered list reflects the new lifecycle immediately.
    $(document).on('sw:shift-changed', function () {
        window.location.reload();
    });

});
</script>
@endpush
