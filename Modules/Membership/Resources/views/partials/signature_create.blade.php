<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">{{ __('messages.add') }} {{ __('membership::lang.authorized_signature') }}</h4>
        </div>
        {!! Form::open([
            'action' => ['\Modules\Membership\Http\Controllers\MembershipSettingController@storeSignature'],
            'method' => 'post',
            'id' => 'add_signature_form',
            'enctype' => 'multipart/form-data'
        ]) !!}
        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('signature', __('membership::lang.signature') . ' *') !!}
                {!! Form::file('signature', [
                    'class' => 'form-control', 
                    'required',
                      'accept' => 'image/*,.pdf,.doc,.docx,.webp,.bmp,.tiff,.tif',
                    'id' => 'signature_file'
                ]) !!}
                
                <!-- Image preview container -->
              <div class="mt-3" id="image_preview_container" style="display:none;">
                <h6>{{ __('membership::lang.preview') }}:</h6>
                <div class="border p-2 text-center bg-light rounded">
                    <img id="signature_preview"
                        src=""
                        class="img-fluid mb-2"
                        style="max-height:150px; display:none;">

                    <div id="file_info" class="text-muted" style="display:none;"></div>
                </div>
            </div>


                
                <small class="help-block mt-2">{{ __('membership::lang.signature_help_text') }}</small>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> {{ __('messages.save') }}
            </button>
        </div>
        {!! Form::close() !!}
    </div>
</div>

