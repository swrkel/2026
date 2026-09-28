<div class="erp-ui-page-header">
    <div>
        <h1 class="erp-ui-page-title">{{ $title ?? 'Page' }}</h1>
        @if(!empty($subtitle))<div class="erp-ui-page-subtitle">{{ $subtitle }}</div>@endif
    </div>
    @if(!empty($actions))<div class="erp-ui-actions">{!! $actions !!}</div>@endif
</div>
