@extends('airlineticketingnew::layouts.app')
@section('atn-title','Transport Vehicles')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>Vehicle No</th><th>Vehicle Type</th><th>Make</th><th>Model</th><th>Seat Capacity</th><th>Driver Name</th><th>Is Active</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->vehicle_no }}</td><td>{{ $record->vehicle_type }}</td><td>{{ $record->make }}</td><td>{{ $record->model }}</td><td>{{ $record->seat_capacity }}</td><td>{{ $record->driver_name }}</td><td>{{ $record->is_active }}</td></tr>@empty<tr><td colspan="7" class="text-center">No records</td></tr>@endforelse
</tbody></table></div>{{ $records->links() }}</div>
@endsection
