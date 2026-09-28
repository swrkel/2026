@extends('airlineticketingnew::layouts.app')
@section('atn-title','Staff Incentives')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Incentive No</th><th>Date</th><th>User</th><th>Ticket</th><th>Amount</th><th>Due</th><th>Status</th></tr></thead>
<tbody>@forelse($records as $record)<tr><td>{{ $record->incentive_no }}</td><td>{{ $record->incentive_date }}</td><td>{{ $record->user_id }}</td><td>{{ $record->ticket_id }}</td><td class="text-right">{{ number_format((float)$record->incentive_amount,4) }}</td><td class="text-right">{{ number_format((float)$record->due_amount,4) }}</td><td>{{ $record->status }}</td></tr>@empty<tr><td colspan="7" class="text-center">No records</td></tr>@endforelse</tbody>
</table></div>{{ $records->links() }}</div>
@endsection
