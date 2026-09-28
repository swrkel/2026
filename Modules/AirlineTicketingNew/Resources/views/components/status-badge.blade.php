@props(['status'])
@php
$map=['active'=>'success','paid'=>'success','completed'=>'success','approved'=>'success','pending'=>'warning','open'=>'warning','cancelled'=>'danger','rejected'=>'danger','failed'=>'danger','draft'=>'default'];
$class=$map[$status]??'info';
@endphp
<span class="label label-{{ $class }}">{{ ucfirst(str_replace('_',' ',$status)) }}</span>
