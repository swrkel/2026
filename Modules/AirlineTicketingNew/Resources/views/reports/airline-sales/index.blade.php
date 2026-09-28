@extends('airlineticketingnew::layouts.app')
@section('atn-title','Airline Sales Report')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-toolbar"><form method="GET" class="atn-inline-filters"><input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}"><input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}"><button class="btn btn-primary">Apply</button></form></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Airline</th><th>Currency</th><th>Tickets</th><th>Sales</th><th>Tax</th></tr></thead>
<tbody>@forelse($records as $record)<tr><td>{{ $record->airline_name }}</td><td>{{ $record->currency_code }}</td><td>{{ $record->ticket_count }}</td><td class="text-right">{{ number_format((float)$record->sales_total,4) }}</td><td class="text-right">{{ number_format((float)$record->tax_total,4) }}</td></tr>@empty<tr><td colspan="5" class="text-center">No records</td></tr>@endforelse</tbody>
</table></div>{{ $records->links() }}</div>
@endsection
