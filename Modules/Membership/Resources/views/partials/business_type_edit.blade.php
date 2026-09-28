<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => url('/membership/setting/business-types/' . $businessType->id),
                        'method' => 'put', 'id' => 'edit_business_type_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('membership::lang.edit_business_type')</h4>
        </div>
        <div class="modal-body">
            <div id="edit_business_type_rows_container">
                <div class="form-group business-type-edit-row">
                    <label>{{ __('membership::lang.business_type') }}:*</label>
                    <div class="input-group">
                        <input type="text" class="form-control business-type-edit-input" placeholder="{{ __('membership::lang.business_type') }}" value="{{ old('business_type', $businessType->business_type) }}" autocomplete="off">
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-primary add-edit-business-type-row" title="{{ __('messages.add') }}" style="height: 34px;">
                                <i class="fa fa-plus"></i>
                            </button>
                        </span>
                    </div>
                </div>
            </div>
            <input type="hidden" name="business_type" id="edit_form_business_type" value="{{ old('business_type', $businessType->business_type) }}">
            <input type="hidden" name="additional_business_types" id="edit_form_additional_business_types" value="">
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal" data-bs-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
