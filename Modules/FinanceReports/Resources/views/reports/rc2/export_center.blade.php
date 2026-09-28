@extends('layouts.app')
@section('title', 'Finance Reports - Export Center')
@section('content')
<section class="content-header"><h1>Export Center - New</h1></section>
<section class="content">
    @include('financereports::layouts.filter')
@include('financereports::layouts.toolbar')
    <div class="row">
        @foreach($formats as $format)
            <div class="col-md-3 col-sm-6"><div class="small-box bg-aqua"><div class="inner"><h3>{{ strtoupper($format) }}</h3><p>Enabled</p></div><div class="icon"><i class="fa fa-download"></i></div></div></div>
        @endforeach
    </div>
    <div class="box box-success"><div class="box-body">All exports are read-only and generated from Finance Reports data services.</div></div>
</section>
@endsection
