@props(['label','value','description'=>null,'icon'=>'fa-bar-chart','tone'=>'blue','href'=>null])
@php
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif class="pn-stat-card pn-tone-{{ $tone }}">
    <span class="pn-stat-icon"><i class="fa {{ $icon }}"></i></span>
    <span class="pn-stat-copy"><small>{{ $label }}</small><strong>{{ $value }}</strong>@if($description)<em>{{ $description }}</em>@endif</span>
</{{ $tag }}>
