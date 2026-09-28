@props([
    'label' => null,
    'for' => null,
    'required' => false,
    'error' => null,
])
<div class="form-group erp-form-group">
    @if($label)
        <label @if($for) for="{{ $for }}" @endif class="erp-form-label">
            {{ $label }} @if($required)<span class="text-danger">*</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if($error)
        <span class="help-block text-danger erp-form-error">{{ $error }}</span>
    @endif
</div>
