@extends('layouts.app')
@section('title', 'Sample Tracking')
@section('content')
<section class="content-header"><h1>Sample Tracking</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Samples</h3><div class="box-tools"><a href="{{ route('myhealth.laboratory.samples.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Collect Sample</a></div></div>
<div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Sample No</th><th>Barcode</th><th>Member ID</th><th>Sample Type</th><th>Priority</th><th>Status</th><th>Collected At</th><th>Action</th></tr></thead><tbody>
@forelse($samples as $sample)<tr><td>{{ $sample->sample_no }}</td><td>{{ $sample->barcode }}</td><td>{{ $sample->member_id }}</td><td>{{ $sample->sample_type }}</td><td>{{ ucfirst($sample->priority) }}</td><td><span class="label label-info">{{ ucfirst($sample->status) }}</span></td><td>{{ optional($sample->collected_at)->format('Y-m-d H:i') }}</td><td><form method="POST" action="{{ route('myhealth.laboratory.samples.stage', $sample) }}">@csrf<select name="status" class="form-control input-sm" onchange="this.form.submit()"><option value="received">Received</option><option value="processing">Processing</option><option value="verified">Verified</option><option value="approved">Approved</option><option value="released">Released</option><option value="rejected">Rejected</option></select></form></td></tr>@empty<tr><td colspan="8" class="text-center">No samples found.</td></tr>@endforelse
</tbody></table>{{ $samples->links() }}</div></div>
</section>
@endsection
