@extends('layouts.app')
@section('title', 'Operative Records')
@section('content')
<section class="content-header"><h1>Operative Records <a href="{{ route('myhealth.operation_theatre.records.create') }}" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> Add</a></h1></section>
<section class="content">@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif<div class="box box-primary"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Operation No</th><th>Surgery ID</th><th>Member</th><th>Procedure</th><th>Anaesthesia</th><th>Status</th></tr></thead><tbody>@forelse($records as $row)<tr><td>{{ $row->operation_no }}</td><td>{{ $row->surgery_schedule_id }}</td><td>{{ $row->member_id }}</td><td>{{ $row->procedure_performed }}</td><td>{{ $row->anaesthesia_type }}</td><td>{{ ucfirst($row->status) }}</td></tr>@empty<tr><td colspan="6" class="text-center">No records found.</td></tr>@endforelse</tbody></table>{{ $records->links() }}</div></div></section>
@endsection
