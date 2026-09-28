@extends('distributionnew::layouts.app')
@section('content')
<div class="pos-page disnew-page">
    <div class="pos-card disnew-card">
        <div class="pos-card-header d-flex justify-content-between align-items-center">
            <h4>Trips / Vehicle Operations</h4>
            <div class="pos-toolbar">Search | Date Range | CSV | Excel | PDF | Print | Column Visibility</div>
        </div>
        <div class="pos-card-body">
            <table class="table table-bordered table-striped"><thead><tr><th>Trip No</th><th>Date</th><th>Vehicle</th><th>Route</th><th>Status</th><th>Action</th></tr></thead><tbody>@foreach($trips as $trip)<tr><td>{{ $trip->trip_no }}</td><td>{{ $trip->trip_date }}</td><td>{{ $trip->vehicle_id }}</td><td>{{ $trip->route_id }}</td><td>{{ ucfirst($trip->status) }}</td><td><button class="btn btn-primary btn-sm">View</button></td></tr>@endforeach</tbody></table>{{ $trips->links() }}
        </div>
    </div>
</div>
@endsection
