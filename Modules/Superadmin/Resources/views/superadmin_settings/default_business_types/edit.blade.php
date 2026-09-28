<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('\Modules\Superadmin\Http\Controllers\DefaultBusinessTypeController@update', $business_type->id),
                        'method' => 'put', 'id' => 'edit_business_type_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('superadmin::lang.edit_default_business_type')</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('business_type', __('superadmin::lang.business_type') . ':*') !!}
                {!! Form::text('business_type', $business_type->business_type,
                    ['class' => 'form-control', 'required', 'placeholder' => __('superadmin::lang.business_type')]) !!}
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
