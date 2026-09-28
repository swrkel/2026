@extends('layouts.app')
@section('title', 'Pre-Operative Checklists')
@section('content')
<section class="content-header"><h1>Pre-Operative Checklists <a href="{{ route('myhealth.operation_theatre.checklists.create') }}" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> Add</a></h1></section>
<section class="content">@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif<div class="box box-primary"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Surgery ID</th><th>Member</th><th>Status</th><th>Checked By</th><th>Checked At</th></tr></thead><tbody>@forelse($checklists as $row)<tr><td>{{ $row->surgery_schedule_id }}</td><td>{{ $row->member_id }}</td><td><span class="label label-{{ $row->checklist_status == 'completed' ? 'success' : 'warning' }}">{{ ucfirst($row->checklist_status) }}</span></td><td>{{ $row->checked_by }}</td><td>{{ optional($row->checked_at)->format('Y-m-d H:i') }}</td></tr>@empty<tr><td colspan="5" class="text-center">No records found.</td></tr>@endforelse</tbody></table>{{ $checklists->links() }}</div></div></section>
@endsection
