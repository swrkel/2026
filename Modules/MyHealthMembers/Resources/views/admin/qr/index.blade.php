@extends('layouts.app')
@section('title', 'My Health QR Manager')
@section('content')
<section class="content-header"><h1>QR Manager</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body table-responsive">@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif<table class="table table-bordered table-striped"><thead><tr><th>Member Code</th><th>Name</th><th>QR Status</th><th>Action</th></tr></thead><tbody>
@foreach($members as $member)<tr><td>{{ $member->member_code ?? $member->code ?? $member->id }}</td><td>{{ $member->name ?? '-' }}</td><td>{{ !empty($member->qr_token) ? 'Active' : 'Not Issued' }}</td><td><form style="display:inline" method="POST" action="{{ route('myhealth.admin.qr.reissue', $member->id) }}">@csrf<button class="btn btn-xs btn-primary">Reissue</button></form> <form style="display:inline" method="POST" action="{{ route('myhealth.admin.qr.revoke', $member->id) }}">@csrf<button class="btn btn-xs btn-danger">Revoke</button></form></td></tr>@endforeach
</tbody></table>{{ $members->links() }}</div></div></section>
@endsection
