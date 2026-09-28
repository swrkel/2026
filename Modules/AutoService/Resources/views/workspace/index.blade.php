@extends('autoservice::layouts.master')
@section('content')
@include('autoservice::layouts.nav')
<section class="content-header"><h1>Service Advisor Workspace</h1><p class="text-muted">One screen for front office daily workflow.</p></section>
<section class="content auto-service-workspace">
<div class="row">
@foreach($counts as $label => $count)
 <div class="col-md-2 col-sm-4 col-xs-6"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $count }}</h3><p>{{ ucwords(str_replace('_',' ', $label)) }}</p></div></div></div>
@endforeach
</div>
<div class="row">
@php
$blocks = [
 'Today Appointments'=>$appointments_today,
 'Vehicles Checked In'=>$vehicles_received,
 'Awaiting Estimate'=>$awaiting_estimate,
 'Estimates Awaiting Approval'=>$awaiting_approval,
 'Work In Progress'=>$in_progress,
 'Ready For Delivery'=>$ready_delivery,
 'Due Follow-ups / Reminders'=>$due_reminders,
];
@endphp
@foreach($blocks as $title=>$rows)
<div class="col-md-6"><div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">{{ $title }}</h3></div><div class="box-body table-responsive no-padding"><table class="table table-condensed table-hover">
<thead><tr><th>No</th><th>Status</th><th>Date</th><th>Action</th></tr></thead><tbody>
@forelse($rows as $r)
<tr><td>{{ $r->job_no ?? $r->estimate_no ?? $r->appointment_no ?? $r->reception_no ?? $r->id }}</td><td>{{ ucfirst($r->status ?? $r->workflow_stage ?? 'pending') }}</td><td>{{ $r->job_date ?? $r->appointment_date ?? $r->send_on ?? $r->created_at ?? '' }}</td><td><span class="label label-info">Open</span></td></tr>
@empty<tr><td colspan="4" class="text-muted">No records found</td></tr>@endforelse
</tbody></table></div></div></div>
@endforeach
</div>
</section>
@endsection
