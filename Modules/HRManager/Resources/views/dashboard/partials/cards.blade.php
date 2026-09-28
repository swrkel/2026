@php
    $cardSet = $cardSet ?? [];
@endphp
<div class="hr-hub-kpi-grid {{ $compact ?? false ? 'hr-compact-grid' : '' }}">
@foreach($cardSet as $card)
    <a class="hr-hub-box {{ $compact ?? false ? 'compact' : '' }} hr-box-{{ $card['color'] ?? 'blue' }}" href="{{ $card['url'] ?? '#' }}">
        <span class="hr-box-icon"><i class="fa {{ $card['icon'] ?? 'fa-circle' }}"></i></span>
        <div class="hr-box-text">
            <strong>{{ $card['title'] }}</strong>
            <h2>{{ $card['value'] }}</h2>
            <p>{{ $card['subtitle'] ?? '' }} @if(!($compact ?? false)) <i class="fa fa-arrow-right"></i> @endif</p>
        </div>
        @if($compact ?? false)<em>Open <i class="fa fa-angle-right"></i></em>@endif
        <div class="hr-sparkline"></div>
    </a>
@endforeach
</div>
