<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close membership-card-setting-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title" id="membershipCardSettingModalLabel">{{ __('messages.edit') }} {{ __('membership::lang.card_setting') }}</h4>
        </div>
        {!! Form::open([
            'url' => url('/membership/setting/card-settings/' . $cardSetting->id),
            'method' => 'post',
            'id' => 'edit_card_setting_form'
        ]) !!}
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('length', __('membership::lang.length') . ' (mm) *') !!}
                    {!! Form::number('length', $cardSetting->length, [
                        'class' => 'form-control membership-card-dimension',
                        'required',
                        'step' => '0.01',
                        'min' => '0.01',
                        'placeholder' => __('membership::lang.enter_length')
                    ]) !!}
                </div>
                <div class="form-group">
                    {!! Form::label('width', __('membership::lang.width') . ' (mm) *') !!}
                    {!! Form::number('width', $cardSetting->width, [
                        'class' => 'form-control membership-card-dimension',
                        'required',
                        'step' => '0.01',
                        'min' => '0.01',
                        'placeholder' => __('membership::lang.enter_width')
                    ]) !!}
                </div>
                <div class="form-group membership-card-sample-wrapper">
                    <label>{{ __('membership::lang.card_sample') }}</label>
                    <div style="padding:10px; background:#f5f5f5; border:1px solid #ddd; border-radius:4px; display:inline-block;">
                        <div class="membership-card-sample-box" style="border:2px solid #333; background:#fff; margin:0 auto;"></div>
                        <div class="membership-card-sample-label small text-muted" style="margin-top:4px;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default membership-card-setting-close" data-dismiss="modal" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> {{ __('messages.update') }}
                </button>
            </div>
        {!! Form::close() !!}
    </div>
</div>
