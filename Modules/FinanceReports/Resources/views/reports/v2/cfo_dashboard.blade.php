@extends('layouts.app')
@section('title', 'CFO Dashboard - New')
@section('content')
<section class="content-header"><h1>CFO Dashboard - New</h1></section>
<section class="content">
@include('financereports::layouts.toolbar', ['title' => 'CFO Dashboard - New'])
@foreach($dashboard as $group => $items)
<div class="box box-solid"><div class="box-header"><h3 class="box-title">{{ ucwords(str_replace('_',' ', $group)) }}</h3></div><div class="box-body"><div class="row">
@if(is_array($items)) @foreach($items as $label => $value)<div class="col-md-3"><strong>{{ ucwords(str_replace('_',' ', $label)) }}</strong><br>{{ is_numeric($value) ? number_format($value, 2) : $value }}</div>@endforeach @else <div class="col-md-12"><h3>{{ $items }}</h3></div> @endif
</div></div></div>
@endforeach
</section>
@endsection
