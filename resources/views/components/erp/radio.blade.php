@props(['name','label'=>null,'value'=>1,'checked'=>false])
<label class="exf-radio"><input type="radio" name="{{ $name }}" value="{{ $value }}" {{ old($name, $checked) ? 'checked' : '' }} {{ $attributes }}> {{ $label ?? $slot }}</label>
