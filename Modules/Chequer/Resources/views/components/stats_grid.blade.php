@php
    $cards = $cards ?? [];
    $stats = $stats ?? [];
@endphp
<div class="cheq-grid">
    @foreach($cards as $card)
        <div class="cheq-stat cheq-stat-{{ $card['class'] ?? 'blue' }}">
            <div class="cheq-stat-head">
                <span class="cheq-stat-icon"><i class="fa {{ $card['icon'] ?? 'fa-chart-line' }}"></i></span>
                <strong>{{ $card['label'] ?? '' }}</strong>
            </div>
            <div class="n">{{ $card['value'] ?? ($stats[$card['key'] ?? ''] ?? 0) }}</div>
            <div class="l">{{ $card['note'] ?? '' }}</div>
        </div>
    @endforeach
</div>
