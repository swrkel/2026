@extends('distributionnew::layouts.app')
@section('content')
<div class="pos-card disnew-page"><div class="pos-card-header"><h4>Credit Notes</h4></div><div class="pos-card-body">
<table class="table table-bordered table-striped disnew-table"><thead><tr><th>Credit Note No</th><th>Date</th><th>Customer</th><th>Status</th><th>Total</th><th>Balance</th><th>Action</th></tr></thead><tbody>
@foreach($creditNotes as $row)<tr><td>{{ $row->credit_note_no }}</td><td>{{ $row->credit_note_date }}</td><td>{{ $row->customer_id }}</td><td>{{ ucfirst($row->status) }}</td><td class="text-right">{{ number_format($row->total_amount, 4) }}</td><td class="text-right">{{ number_format($row->balance_amount, 4) }}</td><td><a class="btn btn-sm btn-info" href="{{ route('distribution-new.credit-notes.show', $row) }}">View</a></td></tr>@endforeach
</tbody></table>{{ $creditNotes->links() }}</div></div>
@endsection
