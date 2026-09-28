@extends('airlineticketingnew::layouts.app')
@section('atn-title','Visa Applications')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>Application No</th><th>Passenger Id</th><th>Country Code</th><th>Visa Type</th><th>Appointment At</th><th>Status</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->application_no }}</td><td>{{ $record->passenger_id }}</td><td>{{ $record->country_code }}</td><td>{{ $record->visa_type }}</td><td>{{ $record->appointment_at }}</td><td>{{ $record->status }}</td></tr>@empty<tr><td colspan="6" class="text-center">No records</td></tr>@endforelse
</tbody></table></div>{{ $records->links() }}</div>
@endsection
