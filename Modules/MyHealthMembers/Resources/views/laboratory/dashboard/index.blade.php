@extends('layouts.app')
@section('title', 'My Health Laboratory')
@section('content')
<section class="content-header"><h1>My Health Laboratory</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="row">
@foreach(['Tests Today'=>'tests_today','Samples Collected'=>'samples_collected','In Progress'=>'in_progress','Awaiting Verification'=>'awaiting_verification','Released'=>'released','Critical Results'=>'critical'] as $label=>$key)
    <div class="col-md-2 col-sm-6"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-flask"></i></span><div class="info-box-content"><span class="info-box-text">{{ $label }}</span><span class="info-box-number">{{ $counts[$key] ?? 0 }}</span></div></div></div>
@endforeach
</div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Laboratory Shortcuts</h3></div><div class="box-body">
<a href="{{ route('myhealth.laboratory.catalogue.index') }}" class="btn btn-app"><i class="fa fa-list"></i> Test Catalogue</a>
<a href="{{ route('myhealth.laboratory.samples.create') }}" class="btn btn-app"><i class="fa fa-barcode"></i> Collect Sample</a>
<a href="{{ route('myhealth.laboratory.samples.index') }}" class="btn btn-app"><i class="fa fa-search"></i> Sample Tracking</a>
<a href="{{ route('myhealth.laboratory.processing.create') }}" class="btn btn-app"><i class="fa fa-edit"></i> Enter Results</a>
<a href="{{ route('myhealth.laboratory.processing.index') }}" class="btn btn-app"><i class="fa fa-check"></i> Verification</a>
</div></div>
</section>
@endsection
