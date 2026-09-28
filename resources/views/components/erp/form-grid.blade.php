@props(['columns' => 12, 'class' => ''])
<div {{ $attributes->merge(['class' => trim('exf-form-grid '.$class)]) }}>{{ $slot }}</div>
