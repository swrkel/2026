@extends('layouts.app')
@section('title', 'Post-Operative Notes')
@section('content')
<section class="content-header"><h1>Post-Operative Notes <a href="{{ route('myhealth.operation_theatre.post_op.create') }}" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> Add</a></h1></section>
<section class="content">@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif<div class="box box-primary"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Surgery ID</th><th>Member</th><th>Recovery</th><th>Pain</th><th>ICU</th><th>Ward</th><th>Noted At</th></tr></thead><tbody>@forelse($notes as $row)<tr><td>{{ $row->surgery_schedule_id }}</td><td>{{ $row->member_id }}</td><td>{{ ucfirst($row->recovery_status) }}</td><td>{{ $row->pain_score }}</td><td>{{ $row->icu_transfer_required ? 'Yes' : 'No' }}</td><td>{{ $row->ward_transfer_required ? 'Yes' : 'No' }}</td><td>{{ optional($row->noted_at)->format('Y-m-d H:i') }}</td></tr>@empty<tr><td colspan="7" class="text-center">No records found.</td></tr>@endforelse</tbody></table>{{ $notes->links() }}</div></div></section>
@endsection
