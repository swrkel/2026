@php
    $toneClass = [
        'blue'=>'rcm-kpi-rice','green'=>'rcm-kpi-production','orange'=>'rcm-kpi-sales','purple'=>'rcm-kpi-yield','gold'=>'rcm-kpi-paddy'
    ][$tone ?? 'blue'] ?? 'rcm-kpi-rice';
@endphp
<div class="rcm-kpi-card {{ $toneClass }}">
    <div class="rcm-kpi-icon"><i class="{{ $icon ?? 'fa fa-bar-chart' }}" aria-hidden="true"></i></div>
    <div class="rcm-kpi-content">
        <div class="rcm-kpi-label">{{ $label }}</div>
        <div class="rcm-kpi-value">{{ $value }}@if(!empty($unit)) <span>{{ $unit }}</span>@endif</div>
        @if(!empty($foot))<div class="rcm-kpi-foot">{{ $foot }}</div>@endif
    </div>
</div>
