@props(['name','label'=>null,'checked'=>false,'value'=>1])
<label class="exf-switch"><input type="checkbox" name="{{ $name }}" value="{{ $value }}" {{ old($name, $checked) ? 'checked' : '' }} {{ $attributes }}> <span>{{ $label ?? $slot }}</span></label>
