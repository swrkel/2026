@extends('hotelmanagement::layouts.app')
@section('hotel_content')
<section class="content-header"><h1>Maintenance <small>Room maintenance and out-of-service control</small></h1></section>
<section class="content">
@include('hotelmanagement::partials.nav')
@if(session('status'))<div class="alert alert-success hm-alert">{{ session('status') }}</div>@endif
<div class="box hm-card">
    <div class="box-header"><h3 class="box-title">Add Work Order</h3></div>
    <div class="box-body">
        <form method="POST" action="{{ route('hotel-management.maintenance.store') }}">@csrf
            <div class="hm-form-grid">
                <div class="form-group"><label>Room</label><select name="room_id" class="form-control"><option value="">General / No Room</option>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->room_no }}</option>@endforeach</select></div>
                <div class="form-group"><label>Work Order No</label><input name="work_order_no" class="form-control" placeholder="Auto if blank"></div>
                <div class="form-group"><label>Category</label><select name="category" class="form-control"><option value="general">General</option><option value="electrical">Electrical</option><option value="plumbing">Plumbing</option><option value="ac">A/C</option><option value="furniture">Furniture</option></select></div>
                <div class="form-group"><label>Priority</label><select name="priority" class="form-control"><option value="normal">Normal</option><option value="urgent">Urgent</option><option value="high">High</option><option value="low">Low</option></select></div>
                <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="open">Open</option><option value="in_progress">In Progress</option><option value="on_hold">On Hold</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
                <div class="form-group"><label>Reported At</label><input name="reported_at" type="datetime-local" class="form-control"></div>
                <div class="form-group"><label>Assigned To</label><input name="assigned_to" class="form-control"></div>
                <div class="form-group"><label>Estimated Cost</label><input name="estimated_cost" type="number" step="0.0001" class="form-control" value="0"></div>
                <div class="form-group hm-grid-span-2"><label>Description</label><input name="description" class="form-control"></div>
            </div>
            <br><button class="btn hm-btn-add">Save Work Order</button>
        </form>
    </div>
</div>
<div class="box hm-card">
    <div class="box-header"><h3 class="box-title">Work Order List</h3></div>
    <div class="box-body">
        @include('hotelmanagement::partials.toolbar')
        <div class="table-responsive"><table class="table hm-table table-striped"><thead><tr><th>Work Order</th><th>Room</th><th>Category</th><th>Priority</th><th>Status</th><th>Reported At</th><th>Assigned To</th><th>Estimated Cost</th><th>Action</th></tr></thead><tbody>
        @forelse($orders as $row)
            <tr><td>{{ $row->work_order_no }}</td><td>{{ $row->room_no ?? '-' }}</td><td>{{ ucfirst($row->category ?? '-') }}</td><td>{{ ucfirst($row->priority ?? '-') }}</td><td><span class="hm-badge {{ $row->status ?? '' }}">{{ ucfirst(str_replace('_',' ', $row->status ?? '-')) }}</span></td><td>{{ $row->reported_at }}</td><td>{{ $row->assigned_to ?? '-' }}</td><td>{{ number_format((float)($row->estimated_cost ?? 0), 4) }}</td><td><form method="POST" action="{{ route('hotel-management.maintenance.destroy', $row->id) }}" onsubmit="return confirm('Delete this work order?')">@csrf @method('DELETE')<button class="btn btn-xs btn-danger">Delete</button></form></td></tr>
        @empty
            <tr><td colspan="9"><div class="hm-empty">No records found.</div></td></tr>
        @endforelse
        </tbody></table></div>
    </div>
</div>
</section>
@endsection
