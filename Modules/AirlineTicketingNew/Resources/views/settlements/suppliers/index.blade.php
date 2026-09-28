@extends('airlineticketingnew::layouts.app')
@section('atn-title','Supplier Settlements')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Settlement No</th><th>Date</th><th>Supplier</th><th>Gross</th><th>Net</th><th>Paid</th><th>Due</th><th>Status</th></tr></thead>
<tbody>@forelse($records as $record)<tr><td>{{ $record->settlement_no }}</td><td>{{ $record->settlement_date }}</td><td>{{ $record->supplier_id }}</td><td class="text-right">{{ number_format((float)$record->gross_payable,4) }}</td><td class="text-right">{{ number_format((float)$record->net_payable,4) }}</td><td class="text-right">{{ number_format((float)$record->paid_amount,4) }}</td><td class="text-right">{{ number_format((float)$record->due_amount,4) }}</td><td>{{ $record->status }}</td></tr>@empty<tr><td colspan="8" class="text-center">No records</td></tr>@endforelse</tbody>
</table></div><div class="atn-pagination">{{ $records->links() }}</div></div>
@endsection
