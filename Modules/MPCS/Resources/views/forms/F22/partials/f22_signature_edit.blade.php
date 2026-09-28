<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">{{ __('messages.edit') }} {{ __('membership::lang.authorized_signature') }}</h4>
        </div>
        {!! Form::open([
            'url' => action([\Modules\MPCS\Http\Controllers\F22FormController::class, 'updateF22Signature'], [$signature->id]),
            'method' => 'put',
            'id' => 'edit_f22_signature_form',
            'enctype' => 'multipart/form-data'
        ]) !!}
        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('signature', __('membership::lang.signature') . ' *') !!}
                {!! Form::file('signature', [
                    'class' => 'form-control',
                    'accept' => 'image/*,.pdf,.doc,.docx',
                    'id' => 'edit_f22_signature_file'
                ]) !!}
                
                <div class="mt-3" id="edit_f22_image_preview_container">
                    <h6>{{ __('membership::lang.current_signature') }}:</h6>
                    <div class="border p-2 text-center bg-light rounded">
                        <img id="edit_f22_signature_preview"
                            src="{{ asset('uploads/' . $signature->signature_path) }}"
                            class="img-fluid mb-2"
                            style="max-height:150px;">
                    </div>
                </div>
                
                <small class="help-block mt-2">{{ __('membership::lang.signature_help_text') }}</small>
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

<script>
    $('#edit_f22_signature_file').on('change', function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                if (file.type.startsWith('image/')) {
                    $('#edit_f22_signature_preview').attr('src', e.target.result).show();
                    $('#edit_f22_image_preview_container h6').text('{{ __('membership::lang.preview_new') }}:');
                }
            };
            reader.readAsDataURL(file);
        }
    });
</script>
