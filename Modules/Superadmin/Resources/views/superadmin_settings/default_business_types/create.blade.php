<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('\Modules\Superadmin\Http\Controllers\DefaultBusinessTypeController@store'),
                        'method' => 'post', 'id' => 'add_business_type_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">Add Default Business Type</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('business_type', 'Default Business Type' . ':*') !!}
                {!! Form::text('business_type', null,
                    ['class' => 'form-control', 'required', 'placeholder' => 'Default Business Type']) !!}
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
