<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open([
            'route' => ['membership.setting.authorized-signatures.update', $signature->id],
            'method' => 'put',
            'id' => 'membership_authorized_signature_edit_form',
            'enctype' => 'multipart/form-data'
        ]) !!}
        <div class="modal-header">
            <button type="button" class="close membership-signature-modal-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('membership::lang.edit_signature')</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                <label>@lang('membership::lang.current_signature')</label>
                @if($signature->signature_path && file_exists(public_path('uploads/' . $signature->signature_path)))
                    @php $ext = strtolower(pathinfo($signature->signature_path, PATHINFO_EXTENSION)); @endphp
                    @if(in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','tiff','tif']))
                        <div><img src="{{ asset('uploads/' . $signature->signature_path) }}" style="max-width:200px; max-height:100px;" alt="Current Signature"></div>
                    @else
                        <div class="text-muted"><i class="fa fa-file"></i> {{ strtoupper($ext) }} file uploaded</div>
                    @endif
                @else
                    <p>@lang('membership::lang.no_signature_uploaded')</p>
                @endif
            </div>

            <div class="form-group">
                {!! Form::label('signature', __('membership::lang.upload_new_signature') . ' *') !!}
                {!! Form::file('signature', [
                    'class' => 'form-control',
                    'id' => 'membership_authorized_signature_edit_file',
                    'accept' => 'image/*,.pdf,.doc,.docx,.webp,.bmp,.tiff,.tif',
                    'required'
                ]) !!}
                <div class="mt-3 membership-authorized-signature-preview-container" style="display:none; margin-top:12px;">
                    <h6>{{ __('membership::lang.preview') }}:</h6>
                    <div class="border p-2 text-center bg-light rounded" style="padding:10px; border:1px solid #ddd;">
                        <img class="membership-authorized-signature-preview-image img-fluid mb-2" src="" style="max-height:150px; display:none;" alt="">
                        <div class="membership-authorized-signature-file-info text-muted" style="display:none;"></div>
                    </div>
                </div>
                <small class="help-block">@lang('membership::lang.signature_help_text')</small>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ __('messages.update') }}
            </button>
            <button type="button" class="btn btn-default membership-signature-modal-close" data-dismiss="modal" data-bs-dismiss="modal">
                @lang('messages.close')
            </button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
