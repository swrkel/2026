@extends('layouts.app')
@section('title', 'My Health System Health')
@section('content')
<section class="content-header"><h1>My Health <small>System Health Monitoring</small></h1></section>
<section class="content">
@include('myhealthmembers::disaster_recovery._nav')
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="box box-primary"><div class="box-body"><form method="POST" action="{{ route('myhealth.disaster_recovery.health.run') }}">@csrf<button class="btn btn-primary"><i class="fa fa-heartbeat"></i> Run Health Checks</button></form></div></div>
<div class="box box-info"><div class="box-header"><h3 class="box-title">Health Check History</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Check</th><th>Status</th><th>Severity</th><th>Checked At</th><th>Message</th></tr></thead><tbody>
@forelse($checks as $check)<tr><td>{{ $check->check_name }}</td><td>{{ ucfirst($check->status) }}</td><td>{{ ucfirst($check->severity) }}</td><td>{{ optional($check->checked_at)->format('Y-m-d H:i') }}</td><td>{{ $check->message }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No health checks found.</td></tr>@endforelse
</tbody></table>{{ $checks->links() }}</div></div>
</section>
@endsection
