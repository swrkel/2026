@props(['name','label'=>null,'value'=>null,'required'=>false,'rows'=>3,'help'=>null,'error'=>null])
<div class="exf-field {{ $error ? 'exf-invalid' : '' }}">
    @if($label)<label for="{{ $name }}" class="exf-label">{{ $label }}@if($required)<span class="exf-required">*</span>@endif</label>@endif
    <textarea name="{{ $name }}" id="{{ $name }}" rows="{{ $rows }}" {{ $required ? 'required' : '' }} {{ $attributes->merge(['class'=>'form-control exf-control']) }}>{{ old($name, $value) }}</textarea>
    @if($help)<span class="exf-help">{{ $help }}</span>@endif
    @if($error)<span class="exf-error">{{ $error }}</span>@endif
</div>
