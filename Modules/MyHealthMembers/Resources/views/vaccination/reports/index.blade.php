@extends('layouts.app')
@section('title', 'Vaccination Reports')
@section('content')
<section class="content-header"><h1>Vaccination Reports</h1></section>
<section class="content">
<div class="row">
@foreach(['Given Today'=>$given_today,'Due / Upcoming'=>$due,'Overdue'=>$overdue] as $label=>$value)
<div class="col-md-4"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-bar-chart"></i></span><div class="info-box-content"><span class="info-box-text">{{ $label }}</span><span class="info-box-number">{{ $value }}</span></div></div></div>
@endforeach
</div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Latest Vaccination Records</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Member</th><th>Vaccine</th><th>Dose</th><th>Date</th><th>Next Due</th><th>Status</th></tr></thead><tbody>
@foreach($records as $r)<tr><td>{{ $r->vaccination_no }}</td><td>{{ $r->member_id }}</td><td>{{ $r->vaccine_id }}</td><td>{{ $r->dose_no }}</td><td>{{ optional($r->date_given)->format('Y-m-d') }}</td><td>{{ optional($r->next_due_date)->format('Y-m-d') }}</td><td>{{ ucfirst($r->status) }}</td></tr>@endforeach
</tbody></table>
</div></div></section>
@endsection
