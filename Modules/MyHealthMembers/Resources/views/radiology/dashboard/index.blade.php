@extends('layouts.app')
@section('title', 'My Health Radiology')
@section('content')
<section class="content-header"><h1>My Health Radiology</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="row">
@foreach(['Requests Today'=>'requests_today','Scheduled Today'=>'scheduled_today','Pending'=>'pending','Performed'=>'performed','Awaiting Report'=>'awaiting_report','Released'=>'released','Critical'=>'critical'] as $label=>$key)
    <div class="col-md-2 col-sm-6"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-file-image-o"></i></span><div class="info-box-content"><span class="info-box-text">{{ $label }}</span><span class="info-box-number">{{ $counts[$key] ?? 0 }}</span></div></div></div>
@endforeach
</div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Radiology Shortcuts</h3></div><div class="box-body">
<a href="{{ route('myhealth.radiology.requests.create') }}" class="btn btn-app"><i class="fa fa-plus"></i> New Request</a>
<a href="{{ route('myhealth.radiology.requests.index') }}" class="btn btn-app"><i class="fa fa-list"></i> Request Register</a>
<a href="{{ route('myhealth.radiology.reports.create') }}" class="btn btn-app"><i class="fa fa-edit"></i> Enter Report</a>
<a href="{{ route('myhealth.radiology.reports.index') }}" class="btn btn-app"><i class="fa fa-check"></i> Reports</a>
</div></div>
<div class="box box-info"><div class="box-header with-border"><h3 class="box-title">Workflow</h3></div><div class="box-body">
<div class="row text-center">
@foreach(['Requested','Scheduled','Performed','Reporting','Verified','Approved','Released'] as $stage)
    <div class="col-md-1 col-sm-3" style="min-width:120px;"><span class="label label-primary" style="display:block;padding:10px;">{{ $stage }}</span></div>
@endforeach
</div>
</div></div>
</section>
@endsection
