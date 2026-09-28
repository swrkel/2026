{{--
    Reopen a closed shift.

    The reason is required, not optional. It is the entire point of the log: an
    entry saying a shift was reopened without saying why leaves the next person
    exactly as puzzled as no entry at all.
--}}

<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => route('sw.shifts.reopen', $shift->id), 'method' => 'post']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">
                @lang('sw::lang.reopen_shift') &mdash; {{ $shift->sw_shift_no }}
            </h4>
        </div>

        <div class="modal-body">

            <div class="alert alert-warning" style="font-size:13px">
                <i class="fa fa-exclamation-triangle"></i>
                @lang('sw::lang.reopen_warning')
            </div>

            <div class="form-group">
                {!! Form::label('reason', __('sw::lang.reason_for_reopening') . ':*') !!}
                {!! Form::textarea('reason', null, [
                    'class' => 'form-control', 'rows' => 3, 'required', 'minlength' => 3,
                    'placeholder' => __('sw::lang.reason_placeholder'),
                ]) !!}
                <span class="help-block" style="margin-bottom:0">
                    @lang('sw::lang.reason_appears_in_logs')
                </span>
            </div>

        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-warning">@lang('sw::lang.reopen_shift')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div>
</div>
