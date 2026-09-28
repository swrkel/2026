@extends('autoservice::layouts.master')
@section('content')
@include('autoservice::layouts.nav')
<section class="content-header"><h1>Mechanic Dashboard</h1></section>
<section class="content">
<form method="get" class="box box-body form-inline"><label>Mechanic</label> <select name="mechanic_id" class="form-control"><option value="">All Mechanics</option>@foreach($mechanics as $m)<option value="{{ $m->id }}" {{ (string)$mechanicId===(string)$m->id?'selected':'' }}>{{ $m->name }}</option>@endforeach</select> <button class="btn btn-primary">Search</button></form>
<div class="row"><div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text">Completed Today</span><span class="info-box-number">{{ $completedToday }}</span></div></div></div><div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-wrench"></i></span><div class="info-box-content"><span class="info-box-text">Assigned Jobs</span><span class="info-box-number">{{ $assignments->count() }}</span></div></div></div></div>
<div class="box"><div class="box-header"><h3 class="box-title">Assigned Job List</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Job No</th><th>Status</th><th>Role</th><th>Estimated Hours</th><th>Actual Hours</th></tr></thead><tbody>@foreach($assignments as $a) @php($job=$jobs[$a->job_id]??null)<tr><td>{{ $job->job_no ?? $a->job_id }}</td><td>{{ $job->status ?? '-' }}</td><td>{{ $a->role ?? '-' }}</td><td>{{ $a->estimated_hours ?? '-' }}</td><td>{{ $a->actual_hours ?? '-' }}</td></tr>@endforeach</tbody></table></div></div>
<div class="box"><div class="box-header"><h3 class="box-title">Recent Timeline</h3></div><div class="box-body"><ul>@foreach($timeline as $t)<li>{{ $t->event_at }} - {{ $t->title ?? $t->status ?? $t->description }}</li>@endforeach</ul></div></div>
</section>
@endsection
