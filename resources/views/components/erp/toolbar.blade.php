@props(['left' => null, 'right' => null])
<div {{ $attributes->merge(['class' => 'erp-exf-toolbar']) }}>
    <div class="erp-exf-toolbar-left">{{ $left ?? $slot }}</div>
    <div class="erp-exf-toolbar-right">{{ $right ?? '' }}</div>
</div>
