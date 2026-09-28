<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open([
            'url' => action('\\Modules\\Vat\\Http\\Controllers\\VatInvoice2PrefixController@update', [$data->id]),
            'method' => 'put',
            'id' => 'vat_invoice2_prefix_edit_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                <i class="fa fa-edit"></i> @lang('messages.edit') @lang('vat::lang.prefix')
            </h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('prefix', __('vat::lang.prefix') . ':') !!}
                        {!! Form::text('prefix', $data->prefix ?? null, [
                            'class' => 'form-control',
                            'placeholder' => __('vat::lang.prefix'),
                            'style' => 'width: 100%;',
                            'autocomplete' => 'off',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('starting_no', __('vat::lang.starting_no') . ':*') !!}
                        {!! Form::text('starting_no', $data->starting_no ?? null, [
                            'class' => 'form-control',
                            'required',
                            'placeholder' => __('vat::lang.starting_no'),
                            'style' => 'width: 100%;',
                            'inputmode' => 'numeric',
                            'autocomplete' => 'off',
                        ]) !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('unit_vat_no_of_decimals', __('vat::lang.unit_vat_no_of_decimals') . ':') !!}
                        {!! Form::number('unit_vat_no_of_decimals', $data->unit_vat_no_of_decimals ?? 2, [
                            'class' => 'form-control',
                            'placeholder' => __('vat::lang.unit_vat_no_of_decimals'),
                            'min' => 0,
                            'max' => 10,
                            'step' => 1,
                            'style' => 'width: 100%;',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group" style="margin-top: 28px;">
                        <label for="unit_vat_rounding_off_required">
                            {!! Form::checkbox(
                                'unit_vat_rounding_off_required',
                                1,
                                (bool) ($data->unit_vat_rounding_off_required ?? false),
                                [
                                    'id' => 'unit_vat_rounding_off_required',
                                    'class' => 'input-icheck',
                                ]
                            ) !!}
                            {{ __('vat::lang.rounding_off_required') }}
                        </label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('sub_total_no_of_decimals', __('vat::lang.sub_total_no_of_decimals') . ':') !!}
                        {!! Form::number('sub_total_no_of_decimals', $data->sub_total_no_of_decimals ?? 2, [
                            'class' => 'form-control',
                            'placeholder' => __('vat::lang.sub_total_no_of_decimals'),
                            'min' => 0,
                            'max' => 10,
                            'step' => 1,
                            'style' => 'width: 100%;',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group" style="margin-top: 28px;">
                        <label for="sub_total_rounding_off_required">
                            {!! Form::checkbox(
                                'sub_total_rounding_off_required',
                                1,
                                (bool) ($data->sub_total_rounding_off_required ?? false),
                                [
                                    'id' => 'sub_total_rounding_off_required',
                                    'class' => 'input-icheck',
                                ]
                            ) !!}
                            {{ __('vat::lang.rounding_off_required') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">
                @lang('messages.update')
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">
                @lang('messages.close')
            </button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
