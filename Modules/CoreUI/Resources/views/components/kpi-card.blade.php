<div class="erp-ui-kpi">
    <div class="erp-ui-kpi-icon"><i class="fa {{ $icon ?? 'fa-bar-chart' }}"></i></div>
    <div>
        <div class="erp-ui-kpi-label">{{ $label ?? '' }}</div>
        <div class="erp-ui-kpi-value">{{ $value ?? '0' }}</div>
        @if(!empty($hint))<small class="text-muted">{{ $hint }}</small>@endif
    </div>
</div>
