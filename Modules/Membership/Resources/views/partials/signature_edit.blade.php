<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open([
            'action' => ['\Modules\Membership\Http\Controllers\MembershipSettingController@updateSignature', $signature->id],
            'method' => 'put',
            'id' => 'edit_signature_form',
            'enctype' => 'multipart/form-data'
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('membership::lang.edit_signature')</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                <label>@lang('membership::lang.current_signature')</label>
                @if($signature->signature_path && file_exists(public_path('uploads/' . $signature->signature_path)))
                    <div>
                        <img src="{{ asset('uploads/' . $signature->signature_path) }}" 
                             style="max-width: 200px; max-height: 100px;" 
                             alt="Current Signature">
                    </div>
                @else
                    <p>@lang('membership::lang.no_signature_uploaded')</p>
                @endif
            </div>

            <div class="form-group">
                {!! Form::label('signature', __('membership::lang.upload_new_signature') . ' *') !!}
                {!! Form::file('signature', ['class' => 'form-control', 'id' => 'edit_signature_file', 'accept' => 'image/*,.pdf,.doc,.docx,.webp,.bmp,.tiff,.tif']) !!}
                <div class="mt-3" id="edit_signature_preview_container" style="display:none;">
                    <h6>{{ __('membership::lang.preview') }}:</h6>
                    <div class="border p-2 text-center bg-light rounded">
                        <img id="edit_signature_preview" src="" class="img-fluid mb-2" style="max-height:150px; display:none;" alt="">
                        <div id="edit_signature_file_info" class="text-muted" style="display:none;"></div>
                    </div>
                </div>
                <small class="help-block">@lang('membership::lang.signature_help_text')</small>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ __('messages.update') }}
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>

