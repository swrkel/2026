@props(['title' => null, 'actions' => null])
<div {{ $attributes->merge(['class' => 'erp-exf-card']) }}>
    @if($title || $actions)
        <div class="erp-exf-card-header">
            @if($title)<h3 class="erp-exf-card-title">{{ $title }}</h3>@endif
            @if($actions)<div>{{ $actions }}</div>@endif
        </div>
    @endif
    <div class="erp-exf-card-body">{{ $slot }}</div>
</div>
