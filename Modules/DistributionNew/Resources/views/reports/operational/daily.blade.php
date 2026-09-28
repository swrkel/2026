@extends('distributionnew::layouts.app')
@section('content')
<div class="disnew-page">

@include('distributionnew::partials.erp-standard-styles')<div class="row">
@foreach($summary as $label => $value)<div class="col-md-2"><div class="disnew-kpi"><span>{{ ucwords(str_replace('_',' ',$label)) }}</span><strong>{{ is_numeric($value) ? number_format($value,4) : $value }}</strong></div></div>@endforeach
</div>
</div>
@endsection
