@props(['name','label'=>null,'checked'=>false,'value'=>1])
<label class="exf-checkbox"><input type="checkbox" name="{{ $name }}" value="{{ $value }}" {{ old($name, $checked) ? 'checked' : '' }} {{ $attributes }}> {{ $label ?? $slot }}</label>
