@props(['class'=>''])
<div {{ $attributes->merge(['class'=>trim('exf-filter-panel '.$class)]) }}>{{ $slot }}</div>
