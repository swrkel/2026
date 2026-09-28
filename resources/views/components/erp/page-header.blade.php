@props(['title' => '', 'subtitle' => '', 'actions' => null])
<div {{ $attributes->merge(['class' => 'erp-exf-page-header']) }}>
    <div>
        @if($title)<h1 class="erp-exf-page-title">{{ $title }}</h1>@endif
        @if($subtitle)<div class="erp-exf-page-subtitle">{{ $subtitle }}</div>@endif
    </div>
    @if($actions || isset($action))
        <div class="erp-exf-page-actions">{{ $actions ?? $action }}</div>
    @endif
</div>
