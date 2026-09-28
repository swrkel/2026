<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => url('/membership/setting/business-types'),
                        'method' => 'post', 'id' => 'add_business_type_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('membership::lang.add_business_type')</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                <label>{{ __('membership::lang.business_type') }}:*</label>
                <div id="business_type_rows_container">
                    <div class="business-type-row" style="display:flex; align-items:center; margin-bottom:8px;">
                        <input type="text"
                               class="form-control business-type-input"
                               placeholder="{{ __('membership::lang.business_type') }}"
                               autocomplete="off"
                               style="border-radius:6px;">
                        <button type="button"
                                class="btn btn-primary add-business-type-row"
                                title="{{ __('messages.add') }}"
                                style="margin-left:8px; border-radius:6px; width:40px; height:34px; padding:0; font-size:18px; flex-shrink:0;">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <input type="hidden" name="business_type" id="add_form_business_type" value="">
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal" data-bs-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
