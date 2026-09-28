<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{{ $driver->image_name ?? __('messages.view') }}</h4>
        </div>
        <div class="modal-body text-center">
            @if(!empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="{{ $driver->image_name ?? 'Logo' }}" style="max-width:100%;max-height:70vh;border:1px solid #ddd;padding:6px;background:#fff;" onerror="this.style.display='none'; document.getElementById('vat-logo-fallback').style.display='block';">
                <div id="vat-logo-fallback" class="alert alert-warning" style="display:none;margin-top:10px;">
                    @lang('messages.something_went_wrong')<br>
                    <a href="{{ $logoUrl }}" target="_blank">@lang('messages.view')</a>
                </div>
            @else
                <div class="alert alert-warning">@lang('messages.no_data_found')</div>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
