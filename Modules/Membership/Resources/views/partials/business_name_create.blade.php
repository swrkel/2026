<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open([
            'url' => url('/membership/setting/business-name'),
            'method' => 'post',
            'id' => 'add_business_name_form'
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">{{ __('membership::lang.add_business_name') }}</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('membership_business_type_id', __('membership::lang.business_type') . ' *') !!}
                {!! Form::select(
                    'membership_business_type_id',
                    $businessTypes,
                    null,
                    ['class' => 'form-control select2', 'required', 'placeholder' => __('membership::lang.please_select')]
                ) !!}
            </div>

            <div id="business_name_rows_container">
                <div class="form-group business-name-row">
                    <label>{{ __('membership::lang.Business_name') }}:*</label>
                    <div class="input-group">
                        <input type="text" name="business_names[]" class="form-control business-name-input" placeholder="{{ __('membership::lang.enter_business_name') }}" autocomplete="off" required>
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-primary add-business-name-row" title="{{ __('messages.add') }}" style="height: 34px;">
                                <i class="fa fa-plus"></i>
                            </button>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ __('messages.save') }}
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
