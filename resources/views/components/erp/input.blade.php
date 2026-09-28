@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'help' => null, 'error' => null, 'class' => '', 'placeholder' => null])
<div class="exf-field {{ $error ? 'exf-invalid' : '' }}">
    @if($label)<label for="{{ $name }}" class="exf-label">{{ $label }}@if($required)<span class="exf-required">*</span>@endif</label>@endif
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }} {{ $attributes->merge(['class' => trim('form-control exf-control '.$class)]) }}>
    @if($help)<span class="exf-help">{{ $help }}</span>@endif
    @if($error)<span class="exf-error">{{ $error }}</span>@endif
</div>
