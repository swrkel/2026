<div class="erp-ui-card {{ $class ?? '' }}">
    @if(!empty($title))<div class="erp-ui-card-header"><span>{{ $title }}</span>@if(!empty($tools))<span>{!! $tools !!}</span>@endif</div>@endif
    <div class="erp-ui-card-body">{!! $slot ?? '' !!}</div>
</div>
