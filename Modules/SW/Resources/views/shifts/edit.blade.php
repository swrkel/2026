{{--
    Edit an SW Shift.

    A modal, for the same reason as create.

    Editing a CLOSED shift asks for a reason, which goes to SW Logs. A closed
    shift's figures have been agreed; correcting one is legitimate, changing it
    unnoticed is not.
--}}

<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => route('sw.shifts.update', $shift->id), 'method' => 'put', 'id' => 'sw_shift_edit_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">
                @lang('sw::lang.edit_shift') &mdash; {{ $shift->sw_shift_no }}
            </h4>
        </div>

        <div class="modal-body">

            @if (! $shift->isOpen())
                <div class="alert alert-warning" style="font-size:13px">
                    <i class="fa fa-exclamation-triangle"></i>
                    @lang('sw::lang.changing_a_closed_shift')
                </div>
            @endif

            <div class="row">

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, $shift->location_id, [
                            'class' => 'form-control select2', 'style' => 'width:100%', 'disabled',
                        ]) !!}
                        <span class="help-block" style="margin-bottom:0">
                            @lang('sw::lang.location_cannot_change')
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('shift_date', __('sw::lang.date') . ':*') !!}
                        {!! Form::date('shift_date',
                            \Carbon\Carbon::parse($shift->shift_date)->format('Y-m-d'),
                            ['class' => 'form-control', 'required']) !!}
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('shift_name', __('sw::lang.shift_name') . ':') !!}
                        {!! Form::text('shift_name', $shift->shift_name, [
                            'class' => 'form-control', 'maxlength' => 100,
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('start_time', __('sw::lang.start_time') . ':') !!}
                        {!! Form::time('start_time', $shift->start_time, ['class' => 'form-control']) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('end_time', __('sw::lang.end_time') . ':') !!}
                        {!! Form::time('end_time', $shift->end_time, ['class' => 'form-control']) !!}
                        <span class="help-block" style="margin-bottom:0">
                            @lang('sw::lang.times_optional')
                        </span>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('note', __('sw::lang.note') . ':') !!}
                        {!! Form::textarea('note', $shift->note, ['class' => 'form-control', 'rows' => 2]) !!}
                    </div>
                </div>

                @if (! $shift->isOpen())
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('reason', __('sw::lang.reason_for_change') . ':*') !!}
                            {!! Form::textarea('reason', null, [
                                'class' => 'form-control', 'rows' => 2, 'required',
                                'placeholder' => __('sw::lang.reason_placeholder'),
                            ]) !!}
                            <span class="help-block" style="margin-bottom:0">
                                @lang('sw::lang.reason_appears_in_logs')
                            </span>
                        </div>
                    </div>
                @endif

            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> @lang('messages.update')
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
        </div>

        {!! Form::close() !!}

    </div>
</div>
