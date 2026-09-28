@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'sm',
    'icon' => null,
    'href' => null,
    'class' => '',
])
@php $classes = "btn erp-btn erp-btn--{$variant} btn-{$size} {$class}"; @endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<i class="{{ $icon }}"></i>@endif {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<i class="{{ $icon }}"></i>@endif {{ $slot }}
    </button>
@endif
