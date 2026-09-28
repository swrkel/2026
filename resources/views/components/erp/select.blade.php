@props(['name','label'=>null,'options'=>[], 'selected'=>null, 'required'=>false, 'help'=>null, 'error'=>null, 'placeholder'=>'Please Select'])
<div class="exf-field {{ $error ? 'exf-invalid' : '' }}">
    @if($label)<label for="{{ $name }}" class="exf-label">{{ $label }}@if($required)<span class="exf-required">*</span>@endif</label>@endif
    <select name="{{ $name }}" id="{{ $name }}" {{ $required ? 'required' : '' }} {{ $attributes->merge(['class'=>'form-control exf-control']) }}>
        @if($placeholder !== false)<option value="">{{ $placeholder }}</option>@endif
        @foreach($options as $key => $text)
            <option value="{{ $key }}" {{ (string)old($name, $selected) === (string)$key ? 'selected' : '' }}>{{ $text }}</option>
        @endforeach
    </select>
    @if($help)<span class="exf-help">{{ $help }}</span>@endif
    @if($error)<span class="exf-error">{{ $error }}</span>@endif
</div>
