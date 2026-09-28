@php
$index = $index ?? 0;
$pricing = $pricing ?? null;
@endphp

<td>
    <input type="hidden" name="pricing[{{ $index }}][id]" value="{{ $pricing->id ?? '' }}">
    <input type="number" class="form-control" required step="0.01" 
           name="pricing[{{ $index }}][monday]" 
           value="{{ $pricing ? number_format($pricing->price_monday, $currency_precision, '.', '') : '' }}">
</td>
<td>
    <input type="number" class="form-control" required step="0.01" 
           name="pricing[{{ $index }}][tuesday]" 
           value="{{ $pricing ? number_format($pricing->price_tuesday, $currency_precision, '.', '') : '' }}">
</td>
<td>
    <input type="number" class="form-control" required step="0.01" 
           name="pricing[{{ $index }}][wednesday]" 
           value="{{ $pricing ? number_format($pricing->price_wednesday, $currency_precision, '.', '') : '' }}">
</td>
<td>
    <input type="number" class="form-control" required step="0.01" 
           name="pricing[{{ $index }}][thursday]" 
           value="{{ $pricing ? number_format($pricing->price_thursday, $currency_precision, '.', '') : '' }}">
</td>
<td>
    <input type="number" class="form-control" required step="0.01" 
           name="pricing[{{ $index }}][friday]" 
           value="{{ $pricing ? number_format($pricing->price_friday, $currency_precision, '.', '') : '' }}">
</td>
<td>
    <input type="number" class="form-control" required step="0.01" 
           name="pricing[{{ $index }}][saturday]" 
           value="{{ $pricing ? number_format($pricing->price_saturday, $currency_precision, '.', '') : '' }}">
</td>
<td>
    <input type="number" class="form-control" required step="0.01" 
           name="pricing[{{ $index }}][sunday]" 
           value="{{ $pricing ? number_format($pricing->price_sunday, $currency_precision, '.', '') : '' }}">
</td>
