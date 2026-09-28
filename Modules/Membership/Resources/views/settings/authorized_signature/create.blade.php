<div id="membership_authorized_signature_create_template" style="display:none;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close membership-signature-modal-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">{{ __('messages.add') }} {{ __('membership::lang.authorized_signature') }}</h4>
            </div>
            {!! Form::open([
                'route' => 'membership.setting.authorized-signatures.store',
                'method' => 'post',
                'id' => 'membership_authorized_signature_create_form',
                'enctype' => 'multipart/form-data'
            ]) !!}
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('signature', __('membership::lang.signature') . ' *') !!}
                    {!! Form::file('signature', [
                        'class' => 'form-control',
                        'required',
                        'accept' => 'image/*,.pdf,.doc,.docx,.webp,.bmp,.tiff,.tif',
                        'id' => 'membership_authorized_signature_create_file'
                    ]) !!}

                    <div class="mt-3 membership-authorized-signature-preview-container" style="display:none; margin-top:12px;">
                        <h6>{{ __('membership::lang.preview') }}:</h6>
                        <div class="border p-2 text-center bg-light rounded" style="padding:10px; border:1px solid #ddd;">
                            <img class="membership-authorized-signature-preview-image img-fluid mb-2" src="" style="max-height:150px; display:none;" alt="">
                            <div class="membership-authorized-signature-file-info text-muted" style="display:none;"></div>
                        </div>
                    </div>

                    <small class="help-block mt-2">{{ __('membership::lang.signature_help_text') }}</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default membership-signature-modal-close" data-dismiss="modal" data-bs-dismiss="modal">
                    {{ __('messages.close') }}
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> {{ __('messages.save') }}
                </button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
