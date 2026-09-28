@props(['type' => 'button', 'variant' => 'primary', 'icon' => null])
<button type="{{ $type }}" {{ $attributes->merge(['class' => 'btn btn-' . $variant . ' erp-exf-btn']) }}>
    @if($icon)<i class="{{ $icon }}"></i>@endif
    <span>{{ $slot }}</span>
</button>
