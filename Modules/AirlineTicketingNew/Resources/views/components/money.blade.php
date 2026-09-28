@props(['amount'=>0,'currency'=>null,'decimals'=>4])
<span class="atn-money">{{ $currency ? $currency.' ' : '' }}{{ number_format((float)$amount,$decimals) }}</span>
