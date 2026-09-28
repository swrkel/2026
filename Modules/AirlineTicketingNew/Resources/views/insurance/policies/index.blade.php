@extends('airlineticketingnew::layouts.app')
@section('atn-title','Travel Insurance Policies')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Policy No</th><th>Provider Id</th><th>Passenger Id</th><th>Start Date</th><th>End Date</th><th>Premium Amount</th><th>Coverage Amount</th><th>Status</th></tr></thead>
<tbody>
@forelse($records as $record)
<tr><td>{{ $record->policy_no }}</td><td>{{ $record->provider_id }}</td><td>{{ $record->passenger_id }}</td><td>{{ $record->start_date }}</td><td>{{ $record->end_date }}</td><td>{{ $record->premium_amount }}</td><td>{{ $record->coverage_amount }}</td><td>{{ $record->status }}</td></tr>
@empty
<tr><td colspan="8" class="text-center">No records</td></tr>
@endforelse
</tbody></table></div>{{ $records->links() }}</div>
@endsection
