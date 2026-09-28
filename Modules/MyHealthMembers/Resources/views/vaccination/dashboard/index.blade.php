@extends('layouts.app')
@section('title', 'My Health Vaccination')
@section('content')
<section class="content-header"><h1>My Health Vaccination</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="row">
@foreach(['Vaccine Master'=>'vaccines','Vaccinations Today'=>'given_today','Upcoming Vaccinations'=>'upcoming','Overdue Vaccinations'=>'overdue','Certificates Issued'=>'certificates'] as $label=>$key)
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-shield"></i></span><div class="info-box-content"><span class="info-box-text">{{ $label }}</span><span class="info-box-number">{{ $counts[$key] ?? 0 }}</span></div></div></div>
@endforeach
</div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Vaccination Shortcuts</h3></div><div class="box-body">
<a href="{{ route('myhealth.vaccination.vaccines.create') }}" class="btn btn-app"><i class="fa fa-plus"></i> Add Vaccine</a>
<a href="{{ route('myhealth.vaccination.records.create') }}" class="btn btn-app"><i class="fa fa-syringe"></i> Record Vaccination</a>
<a href="{{ route('myhealth.vaccination.schedules.create') }}" class="btn btn-app"><i class="fa fa-calendar"></i> Schedule</a>
<a href="{{ route('myhealth.vaccination.reports.index') }}" class="btn btn-app"><i class="fa fa-bar-chart"></i> Reports</a>
</div></div>
<div class="box box-info"><div class="box-header with-border"><h3 class="box-title">Vaccination Workflow</h3></div><div class="box-body text-center">
@foreach(['Vaccine Master','Schedule','Member Due','Administered','Certificate','Reminder','Adverse Event Monitoring'] as $stage)
<span class="label label-success" style="display:inline-block;padding:10px;margin:4px;min-width:150px;">{{ $stage }}</span>
@endforeach
</div></div>
</section>
@endsection
