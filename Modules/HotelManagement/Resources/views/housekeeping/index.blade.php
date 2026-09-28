@extends('hotelmanagement::layouts.app')
@section('hotel_content')
<section class="content-header hm-content-header">
    <h1>Housekeeping <small>Cleaning schedules, room readiness, lost & found, and linen control</small></h1>
</section>
<section class="content hm-page">
@include('hotelmanagement::partials.nav')
@if(session('status'))<div class="alert alert-success hm-alert">{{ session('status') }}</div>@endif

<div class="row hm-dashboard-row">
    @forelse($roomStatusSummary as $summary)
        <div class="col-md-3 col-sm-6">
            <div class="hm-stat-card">
                <span class="hm-stat-label">{{ ucfirst(str_replace('_', ' ', $summary->housekeeping_status ?: 'Not Set')) }}</span>
                <strong>{{ number_format($summary->total) }}</strong>
            </div>
        </div>
    @empty
        <div class="col-md-3 col-sm-6"><div class="hm-stat-card"><span class="hm-stat-label">Rooms</span><strong>0</strong></div></div>
    @endforelse
</div>

<div class="box hm-box">
    <div class="box-header with-border hm-box-header">
        <h3 class="box-title">New Cleaning Schedule</h3>
    </div>
    <div class="box-body">
        <form method="POST" action="{{ route('hotel-management.housekeeping.schedules.store') }}">
            @csrf
            <div class="hm-form-grid">
                <div class="form-group"><label>Room</label><select name="room_id" class="form-control" required><option value="">Please Select</option>@foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->room_no }}</option>@endforeach</select></div>
                <div class="form-group"><label>Schedule No</label><input name="schedule_no" class="form-control" placeholder="Auto if blank"></div>
                <div class="form-group"><label>Cleaning Type</label><select name="cleaning_type" class="form-control"><option value="departure">Departure</option><option value="stayover">Stayover</option><option value="deep_clean">Deep Clean</option><option value="inspection">Inspection</option></select></div>
                <div class="form-group"><label>Priority</label><select name="priority" class="form-control"><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
                <div class="form-group"><label>Cleaning Date</label><input name="cleaning_date" type="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                <div class="form-group"><label>Scheduled At</label><input name="scheduled_at" type="datetime-local" class="form-control"></div>
                <div class="form-group hm-col-span-2"><label>Notes</label><input name="notes" class="form-control" placeholder="Instructions for attendant"></div>
            </div>
            <button class="btn hm-btn-add">Save Schedule</button>
        </form>
    </div>
</div>

<div class="box hm-box">
    <div class="box-header with-border hm-box-header"><h3 class="box-title">Cleaning Schedule Board</h3></div>
    <div class="box-body">
        @include('hotelmanagement::partials.toolbar')
        <div class="table-responsive"><table class="table hm-table table-striped">
            <thead><tr><th>Schedule No</th><th>Room</th><th>Type</th><th>Priority</th><th>Status</th><th>Cleaning Date</th><th>Scheduled</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($schedules as $row)
                <tr>
                    <td>{{ $row->schedule_no }}</td><td>{{ $row->room_id }}</td><td>{{ ucfirst(str_replace('_',' ',$row->cleaning_type)) }}</td><td>{{ ucfirst($row->priority) }}</td>
                    <td><span class="hm-badge {{ $row->status }}">{{ ucfirst(str_replace('_',' ',$row->status)) }}</span></td><td>{{ $row->cleaning_date }}</td><td>{{ $row->scheduled_at }}</td>
                    <td class="hm-action-cell">
                        <form method="POST" action="{{ route('hotel-management.housekeeping.schedules.status', $row->id) }}" class="hm-inline-form">@csrf<input type="hidden" name="status" value="in_progress"><button class="btn btn-xs hm-btn-edit">Start</button></form>
                        <form method="POST" action="{{ route('hotel-management.housekeeping.schedules.status', $row->id) }}" class="hm-inline-form">@csrf<input type="hidden" name="status" value="completed"><button class="btn btn-xs hm-btn-view">Done</button></form>
                        <form method="POST" action="{{ route('hotel-management.housekeeping.schedules.status', $row->id) }}" class="hm-inline-form">@csrf<input type="hidden" name="status" value="inspected"><button class="btn btn-xs hm-btn-print">Inspect</button></form>
                    </td>
                </tr>
            @empty<tr><td colspan="8"><div class="hm-empty">No housekeeping schedules found.</div></td></tr>@endforelse
            </tbody>
        </table></div>
    </div>
</div>

<div class="box hm-box">
    <div class="box-header with-border hm-box-header"><h3 class="box-title">Quick Task</h3></div>
    <div class="box-body"><form method="POST" action="{{ route('hotel-management.housekeeping.store') }}">@csrf
        <div class="hm-form-grid">
            <div class="form-group"><label>Room</label><select name="room_id" class="form-control" required><option value="">Please Select</option>@foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->room_no }}</option>@endforeach</select></div>
            <div class="form-group"><label>Task No</label><input name="task_no" class="form-control" placeholder="Auto if blank"></div>
            <div class="form-group"><label>Task Type</label><input name="task_type" class="form-control" value="cleaning"></div>
            <div class="form-group"><label>Priority</label><select name="priority" class="form-control"><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
            <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="pending">Pending</option><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="blocked">Blocked</option></select></div>
            <div class="form-group"><label>Scheduled At</label><input name="scheduled_at" type="datetime-local" class="form-control"></div>
            <div class="form-group hm-col-span-2"><label>Notes</label><input name="notes" class="form-control"></div>
        </div><button class="btn hm-btn-add">Save Task</button>
    </form></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="box hm-box"><div class="box-header with-border hm-box-header"><h3 class="box-title">Lost & Found</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.housekeeping.lost-found.store') }}">@csrf
                <div class="hm-form-grid hm-form-grid-2">
                    <div class="form-group"><label>Item Name</label><input name="item_name" class="form-control" required></div>
                    <div class="form-group"><label>Room</label><select name="room_id" class="form-control"><option value="">Please Select</option>@foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->room_no }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Category</label><input name="category" class="form-control"></div>
                    <div class="form-group"><label>Found Date</label><input name="found_date" type="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                    <div class="form-group"><label>Found By</label><input name="found_by" class="form-control"></div>
                    <div class="form-group"><label>Storage Location</label><input name="storage_location" class="form-control"></div>
                    <div class="form-group hm-col-span-2"><label>Description</label><input name="description" class="form-control"></div>
                </div><button class="btn hm-btn-add">Save Item</button>
            </form>
            <hr>
            <div class="table-responsive"><table class="table hm-table table-striped"><thead><tr><th>No</th><th>Item</th><th>Status</th><th>Found</th><th>Action</th></tr></thead><tbody>
                @forelse($lostFoundItems as $item)<tr><td>{{ $item->item_no }}</td><td>{{ $item->item_name }}</td><td><span class="hm-badge {{ $item->status }}">{{ ucfirst($item->status) }}</span></td><td>{{ $item->found_date }}</td><td><form method="POST" action="{{ route('hotel-management.housekeeping.lost-found.claim', $item->id) }}" class="hm-inline-form">@csrf<input type="hidden" name="status" value="claimed"><button class="btn btn-xs hm-btn-view">Claim</button></form></td></tr>@empty<tr><td colspan="5"><div class="hm-empty">No Lost & Found items.</div></td></tr>@endforelse
            </tbody></table></div>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="box hm-box"><div class="box-header with-border hm-box-header"><h3 class="box-title">Linen Movement</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.housekeeping.linen.store') }}">@csrf
                <div class="hm-form-grid hm-form-grid-2">
                    <div class="form-group"><label>Linen Item</label><input name="linen_item" class="form-control" required></div>
                    <div class="form-group"><label>Quantity</label><input name="quantity" type="number" step="0.0001" class="form-control" required></div>
                    <div class="form-group"><label>Movement Type</label><select name="movement_type" class="form-control"><option value="issue">Issue</option><option value="return">Return</option><option value="laundry_out">Laundry Out</option><option value="laundry_in">Laundry In</option><option value="damage">Damage</option></select></div>
                    <div class="form-group"><label>Room</label><select name="room_id" class="form-control"><option value="">Please Select</option>@foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->room_no }}</option>@endforeach</select></div>
                    <div class="form-group"><label>From</label><input name="from_location" class="form-control"></div>
                    <div class="form-group"><label>To</label><input name="to_location" class="form-control"></div>
                    <div class="form-group"><label>Date</label><input name="movement_date" type="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                    <div class="form-group"><label>Notes</label><input name="notes" class="form-control"></div>
                </div><button class="btn hm-btn-add">Post Linen Movement</button>
            </form>
            <hr>
            <div class="table-responsive"><table class="table hm-table table-striped"><thead><tr><th>No</th><th>Item</th><th>Qty</th><th>Type</th><th>Date</th></tr></thead><tbody>
                @forelse($linenMovements as $line)<tr><td>{{ $line->movement_no }}</td><td>{{ $line->linen_item }}</td><td>{{ number_format((float)$line->quantity, 4) }}</td><td>{{ ucfirst(str_replace('_',' ',$line->movement_type)) }}</td><td>{{ $line->movement_date }}</td></tr>@empty<tr><td colspan="5"><div class="hm-empty">No linen movements.</div></td></tr>@endforelse
            </tbody></table></div>
        </div></div>
    </div>
</div>

<div class="box hm-box">
    <div class="box-header with-border hm-box-header"><h3 class="box-title">Task List</h3></div>
    <div class="box-body">@include('hotelmanagement::partials.toolbar')<div class="table-responsive"><table class="table hm-table table-striped"><thead><tr><th>Task No</th><th>Room</th><th>Task Type</th><th>Priority</th><th>Status</th><th>Scheduled At</th></tr></thead><tbody>@forelse($tasks as $row)<tr><td>{{ $row->task_no ?? "" }}</td><td>{{ $row->room_id ?? "" }}</td><td>{{ $row->task_type ?? "" }}</td><td>{{ $row->priority ?? "" }}</td><td><span class="hm-badge {{ $row->status ?? "" }}">{{ ucfirst($row->status ?? "-") }}</span></td><td>{{ $row->scheduled_at ?? "" }}</td></tr>@empty<tr><td colspan="6"><div class="hm-empty">No records found.</div></td></tr>@endforelse</tbody></table></div></div>
</div>
</section>
@endsection
