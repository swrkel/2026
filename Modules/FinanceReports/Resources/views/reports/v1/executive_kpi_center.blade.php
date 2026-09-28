@extends('layouts.app')
@section('title', 'Executive KPI Center - New')
@section('content')
<section class="content-header"><h1>Executive KPI Center - New</h1></section>
<section class="content">
@include('financereports::layouts.toolbar', ['title' => 'Executive KPI Center - New'])
<div class="row">
@foreach($kpis as $kpi)
<div class="col-md-3"><div class="box box-solid"><div class="box-body"><h4>{{ $kpi['name'] }}</h4><h3>{{ $kpi['value'] }}</h3><small>{{ $kpi['basis'] }}</small></div></div></div>
@endforeach
</div>
</section>
@endsection
