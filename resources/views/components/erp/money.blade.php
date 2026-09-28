@props(['name','label'=>null,'value'=>null,'required'=>false,'currency'=>null,'help'=>null,'error'=>null])
<div class="exf-field {{ $error ? 'exf-invalid' : '' }}">
    @if($label)<label for="{{ $name }}" class="exf-label">{{ $label }}@if($required)<span class="exf-required">*</span>@endif</label>@endif
    <div class="exf-input-group">
        @if($currency)<span class="exf-input-prefix">{{ $currency }}</span>@endif
        <input type="text" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $value) }}" {{ $required ? 'required' : '' }} {{ $attributes->merge(['class'=>'form-control exf-control input_number']) }}>
    </div>
    @if($help)<span class="exf-help">{{ $help }}</span>@endif
    @if($error)<span class="exf-error">{{ $error }}</span>@endif
</div>
