<div class="col-xs-6 col-sm-3 erp-document-card">
    <div class="erp-document-thumb">
        @if(!empty($url))
            <a href="{{ $url }}" target="_blank"><img src="{{ $url }}" alt="{{ $label }}"></a>
        @else
            <span class="text-muted"><i class="fa fa-image"></i></span>
        @endif
    </div>
    <div class="erp-document-label">{{ $label }}</div>
</div>
