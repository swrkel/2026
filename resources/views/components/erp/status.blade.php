@props(['type' => 'muted'])
<span {{ $attributes->merge(['class' => 'erp-exf-status erp-exf-status-' . $type]) }}>{{ $slot }}</span>
