<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">{{ __('messages.edit') }} {{ __('membership::lang.card_setting') }}</h4>
        </div>
        {!! Form::open([
            'action' => ['\Modules\Membership\Http\Controllers\MembershipSettingController@updateCardSetting', $cardSetting->id],
            'method' => 'post',
            'id' => 'edit_card_setting_form'
        ]) !!}
        @method('PUT')
        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('length', __('membership::lang.length') . ' (mm) *') !!}
                {!! Form::number('length', $cardSetting->length, [
                    'class' => 'form-control',
                    'required',
                    'step' => '0.01',
                    'min' => '0.01',
                    'placeholder' => __('membership::lang.enter_length')
                ]) !!}
            </div>
            <div class="form-group">
                {!! Form::label('width', __('membership::lang.width') . ' (mm) *') !!}
                {!! Form::number('width', $cardSetting->width, [
                    'class' => 'form-control',
                    'required',
                    'step' => '0.01',
                    'min' => '0.01',
                    'placeholder' => __('membership::lang.enter_width')
                ]) !!}
            </div>
            @php
                $l = (float) $cardSetting->length;
                $w = (float) $cardSetting->width;
                $maxPx = 120;
                $maxMm = ($l > 0 && $w > 0) ? max($l, $w) : 1;
                $wPx = round($maxPx * ($w / $maxMm));
                $hPx = round($maxPx * ($l / $maxMm));
            @endphp
            <div class="form-group" id="edit_form_card_sample_wrapper" style="{{ ($l > 0 && $w > 0) ? '' : 'display:none;' }}">
                <label>{{ __('membership::lang.card_sample') }}</label>
                <div style="padding:10px; background:#f5f5f5; border:1px solid #ddd; border-radius:4px; display:inline-block;">
                    <div id="edit_form_card_sample_box"
                         style="border:2px solid #333; background:#fff; margin:0 auto; width:{{ $wPx }}px; height:{{ $hPx }}px;"></div>
                    <div id="edit_form_card_sample_label" class="small text-muted" style="margin-top:4px;">
                        {{ $cardSetting->length }} mm × {{ $cardSetting->width }} mm
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ __('messages.update') }}
            </button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
