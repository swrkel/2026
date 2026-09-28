@extends('layouts.app')
@section('title', 'Mobile Banking Dashboard')
@section('content')
<section class="content-header"><h1>Mobile Banking Dashboard</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Mobile Banking Dashboard</h3></div>
        <div class="box-body">

<div class="row">
@foreach($summary as $label => $value)
    <div class="col-md-2 col-sm-4"><div class="info-box"><span class="info-box-icon"><i class="fa fa-mobile"></i></span><div class="info-box-content"><span class="info-box-text">{{ ucwords(str_replace('_',' ', $label)) }}</span><span class="info-box-number">{{ $value }}</span></div></div></div>
@endforeach
</div>
<p class="text-muted">Mobile Banking Enterprise module shell is ready for UI testing and workflow extension.</p>

        </div>
    </div>
</section>
@endsection
