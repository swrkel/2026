@php
    $kpiUrl = $card['url'] ?? null;
    $kpiTone = $card['tone'] ?? '';
    $kpiValueClass = $card['value_class'] ?? '';
@endphp
@if($kpiUrl)
<a href="{{ $kpiUrl }}" class="ch-kpi-link">
@endif
    <div class="ch-kpi {{ $kpiTone }}">
        <div class="ch-kpi-top">
            <div class="ch-icon"><i class="fa {{ $card['icon'] ?? 'fa-circle-o' }}"></i></div>
            <div class="label-text">{{ $card['label'] ?? '' }}</div>
        </div>
        <div class="value {{ $kpiValueClass }}">{{ $card['value'] ?? '0' }}</div>
        <div class="hint">
            {{ $card['hint'] ?? '' }}
            @if($kpiUrl)<span class="ch-drill">Open <i class="fa fa-angle-right"></i></span>@endif
        </div>
        <div class="spark"></div>
    </div>
@if($kpiUrl)
</a>
@endif
