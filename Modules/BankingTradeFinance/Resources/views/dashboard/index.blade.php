@extends('layouts.app')
@section('title', 'Trade Finance Dashboard')
@section('content')
<section class="content-header"><h1>Trade Finance Dashboard</h1></section>
<section class="content">
<div class="row">
@foreach($summary as $label => $value)
<div class="col-md-3"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $value }}</h3><p>{{ ucwords(str_replace('_',' ', $label)) }}</p></div></div></div>
@endforeach
</div>
<div class="box box-primary"><div class="box-body"><p>Standalone enterprise Trade Finance module shell installed.</p></div></div>
</section>
@endsection
