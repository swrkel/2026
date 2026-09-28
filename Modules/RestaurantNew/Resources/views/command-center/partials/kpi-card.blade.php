<div class="restnew-kpi-card">
    <div class="restnew-kpi-title">{{ $title ?? '' }}</div>
    <div class="restnew-kpi-value">{{ $value ?? 0 }}</div>
    @if(!empty($hint))<div class="restnew-kpi-hint">{{ $hint }}</div>@endif
</div>
