@props(['type'=>'secondary'])
<span {{ $attributes->merge(['class'=>'exf-badge exf-badge-'.$type]) }}>{{ $slot }}</span>
