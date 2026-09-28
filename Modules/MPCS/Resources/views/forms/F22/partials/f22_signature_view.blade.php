<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">{{ __('messages.view') }} {{ __('membership::lang.authorized_signature') }}</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12 text-center">
                    <img src="{{ asset('uploads/' . $signature->signature_path) }}" 
                         class="img-responsive" 
                         style="max-width: 100%; height: auto; margin: 0 auto; border: 1px solid #ddd; padding: 5px; border-radius: 4px;"
                         alt="Signature">
                </div>
            </div>
            
            <hr>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <p><strong>{{ __('membership::lang.date_time') }}:</strong> <br>{{ $signature->created_at->format('Y-m-d h:i A') }}</p>
                </div>
                <div class="col-md-6 text-right">
                    <p><strong>{{ __('membership::lang.added_by') }}:</strong> <br>
                        {{ $signature->createdBy ? trim($signature->createdBy->first_name . ' ' . $signature->createdBy->last_name) : '' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
        </div>
    </div>
</div>
