@extends('layouts.app')
@section('title', 'Theatre Rooms')
@section('content')
<section class="content-header"><h1>Theatre Rooms <a href="{{ route('myhealth.operation_theatre.theatres.create') }}" class="btn btn-primary pull-right"><i class="fa fa-plus"></i> Add</a></h1></section>
<section class="content">@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif<div class="box box-primary"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Floor</th><th>Status</th></tr></thead><tbody>@forelse($rooms as $row)<tr><td>{{ $row->room_code }}</td><td>{{ $row->room_name }}</td><td>{{ $row->room_type }}</td><td>{{ $row->floor }}</td><td>{{ ucfirst($row->status) }}</td></tr>@empty<tr><td colspan="5" class="text-center">No records found.</td></tr>@endforelse</tbody></table>{{ $rooms->links() }}</div></div></section>
@endsection
