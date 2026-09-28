@extends('airlineticketingnew::layouts.app')
@section('atn-title','Ticket Sales Report')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-toolbar"><form method="GET" class="atn-inline-filters"><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"><button class="btn btn-primary">Apply</button><a class="btn btn-success" href="{{ route('airline-ticketing-new.reports.ticket-sales.csv',request()->query()) }}">CSV</a></form></div>
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>Ticket</th><th>Date</th><th>Airline</th><th>Passenger</th><th>Currency</th><th>Total</th><th>Status</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->ticket_no }}</td><td>{{ $record->issue_date }}</td><td>{{ $record->airline_name }}</td><td>{{ $record->passenger_name }}</td><td>{{ $record->currency_code }}</td><td class="text-right">{{ number_format((float)$record->grand_total,4) }}</td><td>{{ $record->status }}</td></tr>@empty<tr><td colspan="7" class="text-center">No records</td></tr>@endforelse
</tbody></table></div><div class="atn-pagination">{{ $records->links() }}</div></div>
@endsection
