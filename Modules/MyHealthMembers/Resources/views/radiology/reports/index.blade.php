@extends('layouts.app')
@section('title', 'Radiology Reports')
@section('content')
<section class="content-header"><h1>Radiology Reports</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Report Register</h3><div class="box-tools"><a href="{{ route('myhealth.radiology.reports.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Report</a></div></div>
<div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Report No</th><th>Request No</th><th>Member ID</th><th>Findings</th><th>Critical</th><th>Status</th><th>Reported At</th></tr></thead><tbody>
@forelse($reports as $report)<tr><td>{{ $report->report_no }}</td><td>{{ optional($report->request)->request_no }}</td><td>{{ $report->member_id }}</td><td>{{ \Illuminate\Support\Str::limit($report->findings, 80) }}</td><td>{{ $report->critical_finding ? 'Yes' : 'No' }}</td><td>{{ ucfirst($report->status) }}</td><td>{{ optional($report->reported_at)->format('Y-m-d H:i') }}</td></tr>@empty<tr><td colspan="7" class="text-center">No reports found.</td></tr>@endforelse
</tbody></table>{{ $reports->links() }}</div></div>
</section>
@endsection
