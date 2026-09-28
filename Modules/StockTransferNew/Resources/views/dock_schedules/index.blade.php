@extends('stocktransfernew::layouts.app')
@section('content')
<div class="stn-page">
    <div class="stn-header"><h3>{{ __('stocktransfernew::messages.dock_schedules') }}</h3></div>
    <form method="POST" action="{{ route('stock-transfer-new.dock-schedules.store') }}" class="stn-card stn-grid-4">
        @csrf
        <input name="business_id" placeholder="Business ID" required>
        <input name="location_id" placeholder="Location ID" required>
        <input name="store_id" placeholder="Store ID">
        <input name="dock_no" placeholder="Dock No" required>
        <input type="date" name="schedule_date" required>
        <input type="time" name="start_time" required>
        <input type="time" name="end_time" required>
        <input name="capacity_units" placeholder="Capacity Units">
        <textarea name="notes" placeholder="Notes"></textarea>
        <button class="btn btn-primary">{{ __('messages.save') }}</button>
    </form>
    <div class="stn-card">
        <table class="table table-bordered table-striped stn-table">
            <thead><tr><th>Date</th><th>Dock</th><th>Time</th><th>Capacity</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @foreach($schedules as $row)
                <tr>
                    <td>{{ $row->schedule_date }}</td><td>{{ $row->dock_no }}</td><td>{{ $row->start_time }} - {{ $row->end_time }}</td><td>{{ number_format((float)$row->capacity_units, 3) }}</td><td>{{ ucfirst($row->status) }}</td>
                    <td>@if($row->status === 'open')<form method="POST" action="{{ route('stock-transfer-new.dock-schedules.close',$row->id) }}">@csrf<button class="btn btn-sm btn-warning">Close</button></form>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $schedules->links() }}
    </div>
</div>
@endsection
