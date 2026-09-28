@extends('layouts.app')
@section('title', 'My Health Operation Theatre')
@section('content')
<section class="content-header"><h1>My Health Operation Theatre</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="row">
@foreach(['Scheduled Today'=>'scheduled_today','Emergency Today'=>'emergency_today','Completed Today'=>'completed_today','Cancelled Today'=>'cancelled_today','Pending Checklists'=>'pending_checklists','Open Theatres'=>'open_theatres','Operative Records'=>'operative_records','Post-Op Notes'=>'post_op_notes'] as $label=>$key)
    <div class="col-md-3 col-sm-6"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-procedures"></i></span><div class="info-box-content"><span class="info-box-text">{{ $label }}</span><span class="info-box-number">{{ $counts[$key] ?? 0 }}</span></div></div></div>
@endforeach
</div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Operation Theatre Shortcuts</h3></div><div class="box-body">
<a href="{{ route('myhealth.operation_theatre.schedules.create') }}" class="btn btn-app"><i class="fa fa-plus"></i> Schedule Surgery</a>
<a href="{{ route('myhealth.operation_theatre.schedules.index') }}" class="btn btn-app"><i class="fa fa-calendar"></i> OT Schedule</a>
<a href="{{ route('myhealth.operation_theatre.checklists.create') }}" class="btn btn-app"><i class="fa fa-check"></i> Pre-Op Checklist</a>
<a href="{{ route('myhealth.operation_theatre.records.create') }}" class="btn btn-app"><i class="fa fa-edit"></i> Operative Record</a>
<a href="{{ route('myhealth.operation_theatre.post_op.create') }}" class="btn btn-app"><i class="fa fa-heartbeat"></i> Post-Op Note</a>
<a href="{{ route('myhealth.operation_theatre.reports.index') }}" class="btn btn-app"><i class="fa fa-bar-chart"></i> OT Reports</a>
</div></div>
<div class="box box-info"><div class="box-header with-border"><h3 class="box-title">OT Workflow</h3></div><div class="box-body text-center">
@foreach(['Scheduled','Pre-Op Checklist','In Theatre','Operative Record','Recovery','Post-Op','Completed'] as $stage)
<span class="label label-primary" style="display:inline-block;padding:10px;margin:4px;min-width:130px;">{{ $stage }}</span>
@endforeach
</div></div>
</section>
@endsection
