@extends('stockadjustmentnew::layouts.app')
@section('san_title','Adjustment Reasons')
@section('san_content')
<div class="san-toolbar"><a class="btn btn-success" href="{{ route('stock-adjustment-new.reasons.create') }}">Add Reason</a></div>
<table class="table table-bordered"><thead><tr><th>Name</th><th>Code</th><th>Effect</th><th>Approval</th><th>Active</th><th>Action</th></tr></thead><tbody>@foreach($reasons as $reason)<tr><td>{{ $reason->name }}</td><td>{{ $reason->code }}</td><td>{{ $reason->effect }}</td><td>{{ $reason->requires_approval ? 'Yes':'No' }}</td><td>{{ $reason->is_active ? 'Yes':'No' }}</td><td><a href="{{ route('stock-adjustment-new.reasons.edit',$reason) }}">Edit</a></td></tr>@endforeach</tbody></table>{{ $reasons->links() }}
@endsection
