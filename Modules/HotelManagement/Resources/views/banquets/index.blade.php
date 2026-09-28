@extends('hotelmanagement::layouts.app')

@section('hotel_content')
@include('hotelmanagement::partials.nav')
<section class="content-header">
    <h1>Banquet & Events <small>Function hall bookings, packages and event status</small></h1>
</section>
<section class="content">
    @if(session('status'))<div class="alert alert-success hm-alert">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger hm-alert">{{ $errors->first() }}</div>@endif
    <div class="row">
        <div class="col-md-4"><div class="hm-kpi"><div class="hm-kpi-label">Active Halls</div><div class="hm-kpi-value">{{ $halls->where('status','active')->count() }}</div><div class="hm-kpi-sub">Available event spaces</div></div></div>
        <div class="col-md-4"><div class="hm-kpi"><div class="hm-kpi-label">Upcoming Events</div><div class="hm-kpi-value">{{ $events->whereIn('status',['reserved','confirmed'])->count() }}</div><div class="hm-kpi-sub">Reserved / Confirmed</div></div></div>
        <div class="col-md-4"><div class="hm-kpi"><div class="hm-kpi-label">Open Balance</div><div class="hm-kpi-value">{{ number_format($events->sum('balance_amount'), 2) }}</div><div class="hm-kpi-sub">Current selected business/location</div></div></div>
    </div>
    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Create Hall</h3></div>
        <div class="box-body">
            <form method="POST" action="{{ route('hotel-management.banquets.halls.store') }}">@csrf
                <div class="hm-form-grid">
                    <div class="form-group"><label>Hall Name</label><input name="hall_name" class="form-control" required></div>
                    <div class="form-group"><label>Hall Code</label><input name="hall_code" class="form-control" placeholder="Auto if blank"></div>
                    <div class="form-group"><label>Capacity</label><input name="capacity" type="number" class="form-control" value="0"></div>
                    <div class="form-group"><label>Base Rate</label><input name="base_rate" type="number" step="0.0001" class="form-control" value="0"></div>
                    <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option><option value="maintenance">Maintenance</option></select></div>
                    <div class="form-group" style="grid-column:span 3"><label>Description</label><input name="description" class="form-control"></div>
                </div><br><button class="btn hm-btn-add">Save Hall</button>
            </form>
        </div>
    </div>
    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Book Event</h3></div>
        <div class="box-body">
            <form method="POST" action="{{ route('hotel-management.banquets.events.store') }}">@csrf
                <div class="hm-form-grid">
                    <div class="form-group"><label>Date</label><input type="date" name="event_date" class="form-control" required value="{{ date('Y-m-d') }}"></div>
                    <div class="form-group"><label>Start</label><input type="time" name="start_time" class="form-control"></div>
                    <div class="form-group"><label>End</label><input type="time" name="end_time" class="form-control"></div>
                    <div class="form-group"><label>Hall</label><select name="hall_id" class="form-control" required>@foreach($halls as $hall)<option value="{{ $hall->id }}">{{ $hall->hall_name }} ({{ $hall->capacity }})</option>@endforeach</select></div>
                    <div class="form-group"><label>Event Type</label><select name="event_type" class="form-control"><option>Wedding</option><option>Conference</option><option>Meeting</option><option>Birthday</option><option>Seminar</option><option>Other</option></select></div>
                    <div class="form-group"><label>Customer Name</label><input name="customer_name" class="form-control" required></div>
                    <div class="form-group"><label>Mobile</label><input name="customer_mobile" class="form-control"></div>
                    <div class="form-group"><label>Guests</label><input name="guest_count" type="number" class="form-control" value="0"></div>
                    <div class="form-group"><label>Package</label><input name="package_amount" type="number" step="0.0001" class="form-control" value="0"></div>
                    <div class="form-group"><label>Tax</label><input name="tax_amount" type="number" step="0.0001" class="form-control" value="0"></div>
                    <div class="form-group"><label>Discount</label><input name="discount_amount" type="number" step="0.0001" class="form-control" value="0"></div>
                    <div class="form-group"><label>Advance</label><input name="advance_amount" type="number" step="0.0001" class="form-control" value="0"></div>
                    <div class="form-group" style="grid-column:span 4"><label>Note</label><input name="note" class="form-control"></div>
                </div><br><button class="btn hm-btn-add">Book Event</button>
            </form>
        </div>
    </div>
    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Event Register</h3></div>
        <div class="box-body">
            @include('hotelmanagement::partials.toolbar')
            <div class="table-responsive"><table class="table table-bordered hm-table"><thead><tr><th>Event No</th><th>Date</th><th>Time</th><th>Hall</th><th>Customer</th><th>Type</th><th>Guests</th><th>Total</th><th>Balance</th><th>Status</th><th>Action</th></tr></thead><tbody>
                @forelse($events as $event)<tr><td>{{ $event->event_no }}</td><td>{{ $event->event_date }}</td><td>{{ $event->start_time }} - {{ $event->end_time }}</td><td>{{ $event->hall_name }}</td><td>{{ $event->customer_name }}<br><small>{{ $event->customer_mobile }}</small></td><td>{{ $event->event_type }}</td><td>{{ $event->guest_count }}</td><td>{{ number_format($event->total_amount,2) }}</td><td>{{ number_format($event->balance_amount,2) }}</td><td><span class="hm-badge {{ $event->status }}">{{ str_replace('_',' ',ucfirst($event->status)) }}</span></td><td><form method="POST" action="{{ route('hotel-management.banquets.events.status',$event->id) }}" style="display:flex;gap:4px">@csrf<select name="status" class="form-control input-sm"><option value="reserved">Reserved</option><option value="confirmed">Confirmed</option><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select><button class="btn btn-xs btn-primary">Update</button></form></td></tr>
                @empty<tr><td colspan="11"><div class="hm-empty">No banquet events found.</div></td></tr>@endforelse
            </tbody></table></div>
        </div>
    </div>
</section>
@endsection
