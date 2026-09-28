@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
])
<x-erp.forms.group :label="$label" :for="$name" :required="$required" :error="$errors->first($name)">
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $value) }}" {{ $attributes->merge(['class' => 'form-control erp-control']) }} @if($required) required @endif>
</x-erp.forms.group>
