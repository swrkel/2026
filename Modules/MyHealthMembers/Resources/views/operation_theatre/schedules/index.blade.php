@extends('layouts.app')
@section('title', 'Surgery Schedule')
@section('content')
<section class="content-header"><h1>Surgery Schedule <a href="{{ route('myhealth.operation_theatre.schedules.create') }}" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> Add</a></h1></section>
<section class="content">@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="box box-primary"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Surgery No</th><th>Member</th><th>Procedure</th><th>Priority</th><th>Scheduled</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($schedules as $row)<tr><td>{{ $row->surgery_no }}</td><td>{{ $row->member_id }}</td><td>{{ $row->procedure_name }}</td><td>{{ ucfirst($row->priority) }}</td><td>{{ optional($row->scheduled_start_at)->format('Y-m-d H:i') }}</td><td><span class="label label-info">{{ ucfirst($row->status) }}</span></td><td><form method="POST" action="{{ route('myhealth.operation_theatre.schedules.status', $row) }}">@csrf<select name="status" class="form-control input-sm" onchange="this.form.submit()"><option value="">Change</option>@foreach(['pre_op','in_theatre','completed','cancelled','postponed'] as $s)<option value="{{ $s }}">{{ ucfirst(str_replace('_',' ', $s)) }}</option>@endforeach</select></form></td></tr>@empty<tr><td colspan="7" class="text-center">No records found.</td></tr>@endforelse
</tbody></table>{{ $schedules->links() }}</div></div></section>
@endsection
