{{--
    Record a shortage or an excess against a shift.

    Entered by hand, not computed - the settlement is not final until every
    payment has been added, so a figure worked out now would be wrong by the
    time it mattered.

    Any shift may be chosen, open or closed: the cash is usually counted after
    the shift ends, which is when a difference is found.
--}}

<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => $row ? route('sw.shortage-excess.update', $row->id) : route('sw.shortage-excess.store'),
            'method' => $row ? 'put' : 'post',
            'id' => 'sw_shortage_excess_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">
                {{ $row ? __('sw::lang.edit_shortage_excess') : __('sw::lang.add_shortage_excess') }}
            </h4>
        </div>

        <div class="modal-body">
            <div class="row">

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('pump_operator_id', __('sw::lang.pump_operator') . ':*') !!}
                        {!! Form::select('pump_operator_id', $operators, $row->pump_operator_id ?? null, [
                            'class' => 'form-control select2', 'id' => 'sw_sef_operator', 'required',
                            'placeholder' => __('messages.please_select'), 'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('sw_shift_id', __('sw::lang.shift_no') . ':*') !!}
                        <select name="sw_shift_id" id="sw_sef_shift" class="form-control" required>
                            @if ($row)
                                <option value="{{ $row->sw_shift_id }}" selected>{{ $row->sw_shift_no }}</option>
                            @else
                                <option value="">{{ __('sw::lang.choose_an_operator_first') }}</option>
                            @endif
                        </select>
                        <span class="help-block" style="margin-bottom:0">
                            @lang('sw::lang.any_shift_open_or_closed')
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('type', __('sw::lang.type') . ':*') !!}
                        {!! Form::select('type', [
                            'shortage' => __('sw::lang.shortage'),
                            'excess' => __('sw::lang.excess'),
                        ], $row->type ?? 'shortage', [
                            'class' => 'form-control select2', 'required', 'style' => 'width:100%',
                        ]) !!}
                        <span class="help-block" style="margin-bottom:0">
                            @lang('sw::lang.shortage_excess_help')
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('amount', __('sw::lang.amount') . ':*') !!}
                        {!! Form::number('amount', $row->amount ?? null, [
                            'class' => 'form-control', 'step' => '0.01', 'min' => '0.01', 'required',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('note', __('sw::lang.note') . ':') !!}
                        {!! Form::textarea('note', $row->note ?? null, ['class' => 'form-control', 'rows' => 2]) !!}
                    </div>
                </div>

            </div>

            <div class="text-muted" style="font-size:12px;border-top:1px solid #eee;padding-top:10px">
                <i class="fa fa-info-circle"></i> @lang('sw::lang.recorded_not_posted')
            </div>

        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div>
</div>

<script>
$(function () {
    $('#sw_sef_operator').on('change', function () {
        var $shift = $('#sw_sef_shift');
        $shift.prop('disabled', true).empty().append('<option value="">{{ __('sw::lang.loading') }}</option>');

        if (!$(this).val()) {
            $shift.prop('disabled', false).empty()
                .append('<option value="">{{ __('sw::lang.choose_an_operator_first') }}</option>');
            return;
        }

        // all=1: a difference is usually found after the shift closes, so
        // closed shifts must be offered here as well as open ones.
        $.get('{{ route('sw.operators.shifts') }}', {
            pump_operator_id: $(this).val(),
            location_id: $('#sw_se_location_id').val(),
            all: 1
        }, function (rows) {
            $shift.empty();
            if (!rows.length) {
                $shift.append('<option value="">{{ __('sw::lang.no_shifts_for_operator') }}</option>');
            } else {
                $shift.append('<option value="">{{ __('messages.please_select') }}</option>');
                $.each(rows, function (i, r) { $shift.append($('<option>').val(r.id).text(r.label)); });
            }
            $shift.prop('disabled', false);
        });
    });
});
</script>
