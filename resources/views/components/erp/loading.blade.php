@props(['lines'=>3])
<div {{ $attributes->merge(['class'=>'exf-loading']) }}>
    @for($i=0; $i<$lines; $i++)<div class="exf-skeleton" style="height:14px;margin-bottom:8px;width:{{ 95 - ($i*8) }}%;"></div>@endfor
</div>
