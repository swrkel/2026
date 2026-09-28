@extends('airlineticketingnew::layouts.app')
@section('atn-title','Supplier Payments')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Payment No</th><th>Date</th><th>Supplier</th><th>Method</th><th>Reference</th><th>Amount</th><th>Status</th></tr></thead>
<tbody>@forelse($records as $record)<tr><td>{{ $record->payment_no }}</td><td>{{ $record->payment_date }}</td><td>{{ $record->supplier_id }}</td><td>{{ $record->payment_method }}</td><td>{{ $record->reference_no }}</td><td class="text-right">{{ number_format((float)$record->amount,4) }}</td><td>{{ $record->status }}</td></tr>@empty<tr><td colspan="7" class="text-center">No records</td></tr>@endforelse</tbody>
</table></div>{{ $records->links() }}</div>
@endsection
