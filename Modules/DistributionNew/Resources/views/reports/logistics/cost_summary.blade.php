@extends('layouts.app')
@section('title', 'Vehicle Cost Summary')
@section('content')
@include('distributionnew::partials.pos_page_header', ['title' => 'Vehicle Cost Summary'])
<section class="content distributionnew-page"><div class="disnew-pos-card">
@include('distributionnew::partials.report_toolbar')
<div class="row">
@foreach($summary as $label => $amount)
<div class="col-md-3"><div class="small-box bg-aqua"><div class="inner"><h3>{{ number_format($amount, 2) }}</h3><p>{{ ucwords(str_replace('_',' ', $label)) }}</p></div><div class="icon"><i class="fa fa-truck"></i></div></div></div>
@endforeach
</div>
</div></section>
@endsection
