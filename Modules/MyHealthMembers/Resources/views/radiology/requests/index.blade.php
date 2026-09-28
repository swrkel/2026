@extends('layouts.app')
@section('title', 'Radiology Requests')
@section('content')
<section class="content-header"><h1>Radiology Requests</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Request Register</h3><div class="box-tools"><a href="{{ route('myhealth.radiology.requests.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Request</a></div></div>
<div class="box-body">
<form method="GET" class="row" style="margin-bottom:10px;">
<div class="col-md-3"><input name="member_id" value="{{ request('member_id') }}" class="form-control" placeholder="Member ID"></div>
<div class="col-md-3"><select name="modality" class="form-control"><option value="">All Modalities</option>@foreach(['X-Ray','CT','MRI','Ultrasound','ECG','Echo','Mammography','Other'] as $m)<option value="{{ $m }}" {{ request('modality')==$m?'selected':'' }}>{{ $m }}</option>@endforeach</select></div>
<div class="col-md-3"><select name="status" class="form-control"><option value="">All Statuses</option>@foreach(['requested','scheduled','performed','reporting','verified','approved','released','cancelled'] as $s)<option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach</select></div>
<div class="col-md-3"><button class="btn btn-primary"><i class="fa fa-search"></i> Search</button> <a href="{{ route('myhealth.radiology.requests.index') }}" class="btn btn-default">Reset</a></div>
</form>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Request No</th><th>Member ID</th><th>Modality</th><th>Study Type</th><th>Body Part</th><th>Priority</th><th>Scheduled</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($requests as $item)<tr><td>{{ $item->request_no }}</td><td>{{ $item->member_id }}</td><td>{{ $item->modality }}</td><td>{{ $item->study_type }}</td><td>{{ $item->body_part }}</td><td>{{ ucfirst($item->priority) }}</td><td>{{ optional($item->scheduled_at)->format('Y-m-d H:i') }}</td><td><span class="label label-info">{{ ucfirst($item->status) }}</span></td><td><form method="POST" action="{{ route('myhealth.radiology.requests.stage', $item) }}">@csrf<select name="status" class="form-control input-sm" onchange="this.form.submit()"><option value="scheduled">Scheduled</option><option value="performed">Performed</option><option value="reporting">Reporting</option><option value="verified">Verified</option><option value="approved">Approved</option><option value="released">Released</option><option value="cancelled">Cancelled</option></select></form></td></tr>@empty<tr><td colspan="9" class="text-center">No radiology requests found.</td></tr>@endforelse
</tbody></table>{{ $requests->links() }}</div></div></div>
</section>
@endsection
