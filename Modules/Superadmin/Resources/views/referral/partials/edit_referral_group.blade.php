<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('\Modules\Superadmin\Http\Controllers\ReferralGroupController@update', $group->id), 'method' => 'put', 'id' => 'add_pumps_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('superadmin::lang.add_referral_group')</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('date', __( 'superadmin::lang.date' ) . ':*') !!}
                            {!! Form::text('date', @format_date($group->date), ['class' => 'form-control date', 'required',
                            'placeholder' => __( 'superadmin::lang.date' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('group_name', __( 'superadmin::lang.group_name' ) . ':*') !!}
                            {!! Form::text('group_name', $group->group_name, ['class' => 'form-control group_name', 'required',
                            'placeholder' => __( 'superadmin::lang.group_name' ) ]); !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>

        {!! Form::close() !!}
    </div>
</div>

<script>
    $('.date').datepicker('setDate', '{{@format_date($group->date)}}');
</script>

