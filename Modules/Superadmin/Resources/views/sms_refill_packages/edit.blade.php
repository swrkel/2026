<div class="modal-dialog" role="document" data-sms-package-dialog="edit">
    <div class="modal-content">
        {!! Form::open([
            'url' => action('\Modules\Superadmin\Http\Controllers\SmsRefillPackageController@update', [$data->id]),
            'method' => 'put',
            'id' => 'sms_package_edit_form',
            'class' => 'sms-package-form',
            'data-form-type' => 'sms-package',
            'data-form-mode' => 'edit'
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('superadmin::lang.sms_packages')</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('date', __('lang_v1.date') . ':*') !!}
                {!! Form::date('date', !empty($data->date) ? date('Y-m-d', strtotime($data->date)) : date('Y-m-d'), [
                    'class' => 'form-control',
                    'required',
                    'placeholder' => __('lang_v1.date')
                ]) !!}
            </div>

            <div class="form-group">
                {!! Form::label('name', __('superadmin::lang.package_name') . ':*') !!}
                {!! Form::text('name', $data->name, [
                    'class' => 'form-control',
                    'required',
                    'maxlength' => 200,
                    'placeholder' => __('superadmin::lang.package_name')
                ]) !!}
            </div>

            <div class="form-group">
                {!! Form::label('unit_cost', 'Unit Cost per SMS:*') !!}
                {!! Form::number('unit_cost', $data->unit_cost, [
                    'class' => 'form-control',
                    'required',
                    'min' => '0.001',
                    'step' => '0.001',
                    'inputmode' => 'decimal',
                    'placeholder' => 'Unit Cost per SMS'
                ]) !!}
            </div>

            <div class="form-group">
                {!! Form::label('amount', __('superadmin::lang.amount') . ':*') !!}
                {!! Form::number('amount', $data->amount, [
                    'class' => 'form-control',
                    'required',
                    'min' => '0',
                    'step' => '0.00001',
                    'inputmode' => 'decimal',
                    'placeholder' => __('superadmin::lang.amount')
                ]) !!}
            </div>

            <div class="form-group">
                {!! Form::label('no_of_sms', __('superadmin::lang.no_of_sms') . ':') !!}
                {!! Form::number('no_of_sms', $data->no_of_sms, [
                    'class' => 'form-control',
                    'readonly',
                    'tabindex' => '-1',
                    'placeholder' => __('superadmin::lang.no_of_sms')
                ]) !!}
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
