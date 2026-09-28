@extends('airlineticketingnew::layouts.app')
@section('atn-title','Outstanding Invoices')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Invoice No</th><th>Date</th><th>Customer Type</th><th>Currency</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th></tr></thead>
<tbody>@forelse($records as $record)<tr><td>{{ $record->invoice_no }}</td><td>{{ $record->invoice_date }}</td><td>{{ $record->customer_type }}</td><td>{{ $record->currency_code }}</td><td class="text-right">{{ number_format((float)$record->grand_total,4) }}</td><td class="text-right">{{ number_format((float)$record->paid_total,4) }}</td><td class="text-right">{{ number_format((float)$record->due_total,4) }}</td><td>{{ $record->status }}</td></tr>@empty<tr><td colspan="8" class="text-center">No records</td></tr>@endforelse</tbody>
</table></div>{{ $records->links() }}</div>
@endsection
