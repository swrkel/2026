@props(['label' => '', 'value' => '', 'icon' => null])
<div {{ $attributes->merge(['class' => 'erp-exf-kpi']) }}>
    <div class="erp-exf-kpi-label">@if($icon)<i class="{{ $icon }}"></i> @endif{{ $label }}</div>
    <div class="erp-exf-kpi-value">{{ $value }}</div>
</div>
