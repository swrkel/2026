{{--
    Open a new SW Shift.

    Operators are chosen HERE, on the form, rather than in a second step. A
    shift and the people working it are one decision, and splitting them across
    two screens costs a click for no benefit.

    The separate Assign Operators action stays, for changing an assignment
    afterwards.

    Not required, though: someone may need to open a shift before they know who
    is working it. Closing still requires at least one, because a shift with
    nobody assigned has nothing to reconcile.
--}}

<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => route('sw.shifts.store'), 'method' => 'post', 'id' => 'sw_shift_create_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">@lang('sw::lang.open_new_shift')</h4>
        </div>

        <div class="modal-body">
            <div class="row">

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        {!! Form::select('location_id', $business_locations, $locationId ?? null, [
                            'class' => 'form-control select2', 'id' => 'sw_scf_location',
                            'required', 'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('shift_date', __('sw::lang.date') . ':*') !!}
                        {!! Form::date('shift_date', date('Y-m-d'), ['class' => 'form-control', 'required']) !!}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('shift_name', __('sw::lang.shift_name') . ':') !!}
                        {!! Form::text('shift_name', null, [
                            'class' => 'form-control', 'maxlength' => 100,
                            'placeholder' => __('sw::lang.shift_name_placeholder'),
                        ]) !!}
                    </div>
                </div>

                {{-- Times are optional. A shift is identified by its number and
                     date; insisting on the hours would stop someone opening one
                     before they know when it will end. --}}
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('start_time', __('sw::lang.start_time') . ':') !!}
                        {!! Form::time('start_time', null, ['class' => 'form-control']) !!}
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('end_time', __('sw::lang.end_time') . ':') !!}
                        {!! Form::time('end_time', null, ['class' => 'form-control']) !!}
                        <span class="help-block" style="margin-bottom:0">
                            @lang('sw::lang.times_optional')
                        </span>
                    </div>
                </div>

            </div>

            <hr style="margin:10px 0">

            <div class="form-group">
                <label>
                    @lang('sw::lang.pump_operators')
                    <small class="text-muted">&mdash; @lang('sw::lang.operators_optional')</small>
                </label>

                <div id="sw_scf_operators">
                    @if (empty($operators) || count($operators) === 0)
                        <div class="text-muted" style="padding:8px 0">
                            @lang('sw::lang.no_operators_at_location')
                        </div>
                    @else
                        <div class="row">
                            @foreach ($operators as $op)
                                <div class="col-sm-4">
                                    <label class="sw-op-check">
                                        <input type="checkbox" name="operators[]" value="{{ $op->id }}">
                                        <span>{{ $op->name }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('note', __('sw::lang.note') . ':') !!}
                {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 2]) !!}
            </div>

        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> @lang('sw::lang.create_shift')
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
        </div>

        {!! Form::close() !!}

    </div>
</div>


<style>
/*
 | The operator checkboxes.
 |
 | A default checkbox at 13px against a name is hard to read and harder to hit,
 | and this list is used at speed at the start of a shift. Bigger box, bigger
 | name, and the whole row is clickable rather than just the 13 pixels of the
 | box itself.
*/
.sw-op-check {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 400;
    font-size: 15px;
    padding: 9px 12px;
    margin-bottom: 8px;
    border: 1px solid #e4e9f1;
    border-radius: 8px;
    cursor: pointer;
    transition: background .12s, border-color .12s;
}

.sw-op-check:hover {
    background: #f7f9fc;
    border-color: #c9d4e4;
}

.sw-op-check input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin: 0;
    flex: 0 0 18px;
    cursor: pointer;
}

/*
 | A ticked operator should be obvious at a glance across a list of twenty.
 |
 | The class is toggled in JS rather than using :has(), which older browsers do
 | not support - and a ticked operator going unhighlighted is precisely the case
 | this styling exists for.
*/
.sw-op-check.is-checked {
    background: #eef5ff;
    border-color: #7ea6dd;
    font-weight: 600;
}
</style>

<script>
$(function () {
    /*
     | Operators belong to a location, so changing the location must reload the
     | list. Fetched rather than reloading the page - the modal would close, and
     | anything already typed would be lost.
    */
    // Keep the highlight in step with the box, including on rows added when
    // the location changes.
    $(document).on('change', '.sw-op-check input[type="checkbox"]', function () {
        $(this).closest('.sw-op-check').toggleClass('is-checked', this.checked);
    });

    $('#sw_scf_location').on('change', function () {
        var $box = $('#sw_scf_operators');
        $box.html('<div class="text-muted" style="padding:8px 0">{{ __('sw::lang.loading') }}</div>');

        $.get('{{ route('sw.shifts.location-operators') }}', { location_id: $(this).val() },
            function (rows) {
                if (!rows.length) {
                    $box.html('<div class="text-muted" style="padding:8px 0">'
                        + '{{ __('sw::lang.no_operators_at_location') }}</div>');
                    return;
                }

                var html = '<div class="row">';
                $.each(rows, function (i, r) {
                    html += '<div class="col-sm-4">'
                        + '<label class="sw-op-check">'
                        + '<input type="checkbox" name="operators[]" value="' + r.id + '">'
                        + '<span>' + $('<div>').text(r.name).html() + '</span>'
                        + '</label></div>';
                });
                html += '</div>';
                $box.html(html);
            }
        ).fail(function () {
            $box.html('<div class="text-danger" style="padding:8px 0">'
                + '{{ __('sw::lang.could_not_load_operators') }}</div>');
        });
    });
});
</script>
