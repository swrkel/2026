@extends('layouts.app')
@section('title', $title ?? 'My Health Nursing')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'My Health Nursing' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

@php($title = 'My Health Nursing Dashboard')
<div class="row">
@foreach(['Vitals Pending'=>'vitals_pending','Medication Due'=>'medications_due','Critical Alerts'=>'critical_alerts','Shift Handovers'=>'handovers'] as $label=>$key)
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-heartbeat"></i></span><div class="info-box-content"><span class="info-box-text">{{ $label }}</span><span class="info-box-number">{{ $counts[$key] ?? 0 }}</span></div></div></div>
@endforeach
</div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Nursing Shortcuts</h3></div><div class="box-body">
<a href="{{ route('myhealth.nursing.station.index') }}" class="btn btn-app"><i class="fa fa-hospital-o"></i> Nursing Station</a>
<a href="{{ route('myhealth.nursing.vitals.create') }}" class="btn btn-app"><i class="fa fa-heartbeat"></i> Record Vitals</a>
<a href="{{ route('myhealth.nursing.notes.create') }}" class="btn btn-app"><i class="fa fa-file-text-o"></i> Nursing Note</a>
<a href="{{ route('myhealth.nursing.medications.create') }}" class="btn btn-app"><i class="fa fa-medkit"></i> MAR</a>
<a href="{{ route('myhealth.nursing.handovers.create') }}" class="btn btn-app"><i class="fa fa-exchange"></i> Handover</a>
</div></div>
</section>
@endsection
