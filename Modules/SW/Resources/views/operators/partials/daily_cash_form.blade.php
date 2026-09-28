{{--
    Add / edit a Daily Cash collection.

    Operator first, then that operator's OPEN shifts - the flow 8042 describes.
    The Collection Form No is allocated when the entry is saved, not now: a form
    abandoned here should leave no gap in the sequence.
--}}

<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => $row ? route('sw.daily-cash.update', $row->id) : route('sw.daily-cash.store'),
            'method' => $row ? 'put' : 'post',
            'id' => 'sw_daily_cash_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                {{ $row ? __('sw::lang.edit_daily_cash') : __('sw::lang.add_daily_cash') }}
                @if ($row && $row->collection_form_no)
                    <small class="text-muted">
                        &mdash; @lang('sw::lang.collection_form_no') {{ $row->collection_form_no }}
                    </small>
                @endif
            </h4>
        </div>

        <div class="modal-body">
            <div class="row">

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('pump_operator_id', __('sw::lang.pump_operator') . ':*') !!}
                        {!! Form::select('pump_operator_id', $operators, $row->pump_operator_id ?? null, [
                            'class' => 'form-control select2',
                            'id' => 'sw_dcf_operator',
                            'required',
                            'placeholder' => __('messages.please_select'),
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('sw_shift_id', __('sw::lang.shift_no') . ':*') !!}
                        <select name="sw_shift_id" id="sw_dcf_shift" class="form-control" required>
                            @if ($row)
                                <option value="{{ $row->sw_shift_id }}" selected>{{ $row->sw_shift_no }}</option>
                            @else
                                <option value="">{{ __('sw::lang.choose_an_operator_first') }}</option>
                            @endif
                        </select>
                        <span class="help-block" style="margin-bottom:0">
                            @lang('sw::lang.open_shifts_only')
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {{-- What the operator is already carrying, filled when
                             one is chosen. Read-only: it is a fact about the
                             past, not something to type over. --}}
                        {!! Form::label('previous_amount', __('sw::lang.previous_amount') . ':') !!}
                        {!! Form::text('previous_amount', null, [
                            'class' => 'form-control text-right',
                            'id' => 'sw_previous_amount',
                            'readonly',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('current_amount', __('sw::lang.current_amount') . ':*') !!}
                        {!! Form::number('current_amount', $row->current_amount ?? null, [
                            'class' => 'form-control', 'step' => '0.01', 'min' => '0', 'required',
                        ]) !!}
                        <span class="help-block" style="margin-bottom:0">
                            @lang('sw::lang.current_amount_help')
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('collection_date', __('sw::lang.date') . ':*') !!}
                        {!! Form::date('collection_date',
                            $row && $row->collection_date
                                ? \Carbon\Carbon::parse($row->collection_date)->format('Y-m-d')
                                : date('Y-m-d'),
                            ['class' => 'form-control', 'required']) !!}
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('note', __('sw::lang.note') . ':') !!}
                        {!! Form::textarea('note', $row->note ?? null, ['class' => 'form-control', 'rows' => 2]) !!}
                    </div>
                </div>

            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

<script>
$(function () {
    // Fills when an operator is chosen - S-697.
    function swLoadPreviousAmount() {
        var id = $('#sw_dcf_operator').val();
        if (!id) { $('#sw_previous_amount').val(''); return; }

        $('#sw_previous_amount').val('{{ __('sw::lang.loading') }}');

        $.get('{{ route('sw.daily-cash.previous-amount') }}', {
            pump_operator_id: id,
            // On Edit the current row cannot be its own "previous" amount.
            exclude_id: {{ $row ? (int) $row->id : 0 }}
        }, function (d) {
            $('#sw_previous_amount').val(
                parseFloat(d.previous_amount || 0).toFixed(2)
            );
        }).fail(function () {
            $('#sw_previous_amount').val('0.00');
        });
    }

    $('#sw_dcf_operator').off('change.swPreviousAmount')
        .on('change.swPreviousAmount', swLoadPreviousAmount);

    // Add and Edit both populate immediately when an operator is already selected.
    swLoadPreviousAmount();
});
</script>

    </div>
</div>

<script>
$(function () {
    // Operator first, then their open shifts - nothing else can be chosen
    // until an operator is, because the shift list depends on it.
    $('#sw_dcf_operator').on('change', function () {
        var operatorId = $(this).val();
        var $shift = $('#sw_dcf_shift');

        $shift.prop('disabled', true).empty()
            .append('<option value="">{{ __('sw::lang.loading') }}</option>');

        if (!operatorId) {
            $shift.prop('disabled', false).empty()
                .append('<option value="">{{ __('sw::lang.choose_an_operator_first') }}</option>');
            return;
        }

        $.get('{{ route('sw.operators.shifts') }}', {
            pump_operator_id: operatorId,
            location_id: $('#sw_dc_location_id').val()
        }, function (rows) {
            $shift.empty();
            if (!rows.length) {
                $shift.append('<option value="">{{ __('sw::lang.no_open_shift') }}</option>');
            } else {
                $shift.append('<option value="">{{ __('messages.please_select') }}</option>');
                $.each(rows, function (i, r) {
                    $shift.append($('<option>').val(r.id).text(r.label));
                });
            }
            $shift.prop('disabled', false);
        }).fail(function () {
            $shift.empty()
                .append('<option value="">{{ __('sw::lang.could_not_load_shifts') }}</option>')
                .prop('disabled', false);
        });
    });
});
</script>
