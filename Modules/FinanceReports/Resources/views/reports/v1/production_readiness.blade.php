@extends('layouts.app')
@section('title', 'Production Readiness - New')
@section('content')
<section class="content-header"><h1>Production Readiness - New</h1></section>
<section class="content">
<div class="box box-success"><div class="box-body">
<ul class="list-group">
@foreach($readiness as $item)
<li class="list-group-item"><i class="fa fa-check text-green"></i> {{ $item }}</li>
@endforeach
</ul>
</div></div>
</section>
@endsection
