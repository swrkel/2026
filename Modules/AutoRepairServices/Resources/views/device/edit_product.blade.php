<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open([
            'url' => action('\Modules\AutoRepairServices\Http\Controllers\DeviceModelController@update_device', $product->id),
            'method' => 'put',
            'id' => 'device_edit_form',
        ]) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title" id="myModalLabel">
                @lang('autorepairservices::lang.edit_device')
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('name', __('autorepairservices::lang.device_name') . ':*') !!}
                        {!! Form::text('name', $product->name, ['class' => 'form-control', 'required']) !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('description', __('lang_v1.description') . ':') !!}
                        {!! Form::textarea('description', $product->product_description, ['class' => 'form-control', 'rows' => '3']) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">
                @lang('messages.close')
            </button>
            <button type="submit" class="btn btn-primary">
                @lang('messages.update')
            </button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
