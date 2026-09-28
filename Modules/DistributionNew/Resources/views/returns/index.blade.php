@extends('distributionnew::layouts.app')
@section('content')
<div class="pos-card disnew-page"><div class="pos-card-header"><h4>Distribution Returns</h4></div><div class="pos-card-body">
<a href="{{ route('distribution-new.returns.create') }}" class="btn btn-primary mb-3">Add Return</a>
<table class="table table-bordered table-striped disnew-table"><thead><tr><th>Return No</th><th>Date</th><th>Customer</th><th>Status</th><th>Total</th><th>Action</th></tr></thead><tbody>
@foreach($returns as $row)<tr><td>{{ $row->return_no }}</td><td>{{ $row->return_date }}</td><td>{{ $row->customer_id }}</td><td>{{ ucfirst($row->status) }}</td><td class="text-right">{{ number_format($row->total_amount, 4) }}</td><td><a class="btn btn-sm btn-info" href="{{ route('distribution-new.returns.show', $row) }}">View</a></td></tr>@endforeach
</tbody></table>{{ $returns->links() }}</div></div>
@endsection
