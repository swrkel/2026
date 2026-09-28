@extends('hotelmanagement::layouts.app')

@section('hotel_content')
@include('hotelmanagement::partials.nav')
<section class="content-header">
    <h1>Conference & Meeting Halls <small>Meeting room booking, equipment, catering and billing control</small></h1>
</section>
<section class="content">
    @if(session('status'))<div class="alert alert-success hm-alert">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger hm-alert">{{ $errors->first() }}</div>@endif

    <div class="row">
        <div class="col-md-3"><div class="hm-kpi"><div class="hm-kpi-label">Active Rooms</div><div class="hm-kpi-value">{{ $rooms->where('status','active')->count() }}</div><div class="hm-kpi-sub">For selected business/location</div></div></div>
        <div class="col-md-3"><div class="hm-kpi"><div class="hm-kpi-label">Upcoming</div><div class="hm-kpi-value">{{ $bookings->whereIn('status',['reserved','confirmed'])->count() }}</div><div class="hm-kpi-sub">Reserved / Confirmed</div></div></div>
        <div class="col-md-3"><div class="hm-kpi"><div class="hm-kpi-label">Catering</div><div class="hm-kpi-value">{{ $bookings->where('catering_required',1)->count() }}</div><div class="hm-kpi-sub">Bookings with catering</div></div></div>
        <div class="col-md-3"><div class="hm-kpi"><div class="hm-kpi-label">Open Balance</div><div class="hm-kpi-value">{{ number_format($bookings->sum('balance_amount'), 2) }}</div><div class="hm-kpi-sub">Pending collection</div></div></div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Create Meeting Room</h3></div>
        <div class="box-body">
            <form method="POST" action="{{ route('hotel-management.conference.rooms.store') }}">@csrf
                <div class="hm-form-grid">
                    <div class="form-group"><label>Room Name</label><input name="room_name" class="form-control" required></div>
                    <div class="form-group"><label>Room Code</label><input name="room_code" class="form-control" placeholder="Auto if blank"></div>
                    <div class="form-group"><label>Capacity</label><input name="capacity" type="number" class="form-control" value="0"></div>
                    <div class="form-group"><label>Setup Style</label><select name="setup_style" class="form-control"><option>Theatre</option><option>Classroom</option><option>Boardroom</option><option>U Shape</option><option>Banquet</option></select></div>
                    <div class="form-group"><label>Hourly Rate</label><input name="hourly_rate" type="number" step="0.0001" class="form-control" value="0"></div>
                    <div class="form-group"><label>Half Day Rate</label><input name="half_day_rate" type="number" step="0.0001" class="form-control" value="0"></div>
                    <div class="form-group"><label>Full Day Rate</label><input name="full_day_rate" type="number" step="0.0001" class="form-control" value="0"></div>
                    <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option><option value="maintenance">Maintenance</option></select></div>
                    <div class="form-group" style="grid-column:span 4"><label>Equipment</label><input name="equipment" class="form-control" placeholder="Projector, sound system, whiteboard, video conference unit"></div>
                </div><br><button class="btn hm-btn-add">Save Meeting Room</button>
            </form>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Book Conference / Meeting</h3></div>
        <div class="box-body">
            <form method="POST" action="{{ route('hotel-management.conference.bookings.store') }}">@csrf
                <div class="hm-form-grid">
                    <div class="form-group"><label>Date</label><input type="date" name="booking_date" class="form-control" required value="{{ date('Y-m-d') }}"></div>
                    <div class="form-group"><label>Start</label><input type="time" name="start_time" class="form-control" required></div>
                    <div class="form-group"><label>End</label><input type="time" name="end_time" class="form-control" required></div>
                    <div class="form-group"><label>Meeting Room</label><select name="conference_room_id" class="form-control" required>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->room_name }} ({{ $room->capacity }})</option>@endforeach</select></div>
                    <div class="form-group"><label>Meeting Title</label><input name="meeting_title" class="form-control" required></div>
                    <div class="form-group"><label>Customer Name</label><input name="customer_name" class="form-control" required></div>
                    <div class="form-group"><label>Mobile</label><input name="customer_mobile" class="form-control"></div>
                    <div class="form-group"><label>Email</label><input name="customer_email" type="email" class="form-control"></div>
                    <div class="form-group"><label>Attendees</label><input name="attendees" type="number" class="form-control" value="0"></div>
                    <div class="form-group"><label>Setup Style</label><select name="setup_style" class="form-control"><option>Theatre</option><option>Classroom</option><option>Boardroom</option><option>U Shape</option><option>Banquet</option></select></div>
                    <div class="form-group"><label>Rental Amount</label><input name="rental_amount" type="number" step="0.0001" class="form-control" value="0"></div>
                    <div class="form-group"><label>Catering Amount</label><input name="catering_amount" type="number" step="0.0001" class="form-control" value="0"></div>
                    <div class="form-group"><label>Tax</label><input name="tax_amount" type="number" step="0.0001" class="form-control" value="0"></div>
                    <div class="form-group"><label>Discount</label><input name="discount_amount" type="number" step="0.0001" class="form-control" value="0"></div>
                    <div class="form-group"><label>Advance</label><input name="advance_amount" type="number" step="0.0001" class="form-control" value="0"></div>
                    <div class="form-group"><label>Catering Required</label><select name="catering_required" class="form-control"><option value="0">No</option><option value="1">Yes</option></select></div>
                    <div class="form-group" style="grid-column:span 2"><label>Equipment Required</label><input name="equipment_required" class="form-control"></div>
                    <div class="form-group" style="grid-column:span 2"><label>Catering Note</label><input name="catering_note" class="form-control"></div>
                    <div class="form-group" style="grid-column:span 4"><label>Internal Note</label><input name="note" class="form-control"></div>
                </div><br><button class="btn hm-btn-add">Save Booking</button>
            </form>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Conference Booking Register</h3></div>
        <div class="box-body">
            @include('hotelmanagement::partials.toolbar')
            <div class="table-responsive"><table class="table table-bordered hm-table"><thead><tr><th>Booking No</th><th>Date</th><th>Time</th><th>Room</th><th>Meeting</th><th>Customer</th><th>Attendees</th><th>Catering</th><th>Total</th><th>Balance</th><th>Status</th><th>Action</th></tr></thead><tbody>
                @forelse($bookings as $booking)<tr><td>{{ $booking->booking_no }}</td><td>{{ $booking->booking_date }}</td><td>{{ $booking->start_time }} - {{ $booking->end_time }}</td><td>{{ $booking->room_name }}</td><td>{{ $booking->meeting_title }}<br><small>{{ $booking->setup_style }}</small></td><td>{{ $booking->customer_name }}<br><small>{{ $booking->customer_mobile }}</small></td><td>{{ $booking->attendees }}</td><td>{{ $booking->catering_required ? 'Yes' : 'No' }}</td><td>{{ number_format($booking->total_amount,2) }}</td><td>{{ number_format($booking->balance_amount,2) }}</td><td><span class="hm-badge {{ $booking->status }}">{{ str_replace('_',' ',ucfirst($booking->status)) }}</span></td><td><form method="POST" action="{{ route('hotel-management.conference.bookings.status',$booking->id) }}" style="display:flex;gap:4px">@csrf<select name="status" class="form-control input-sm"><option value="reserved">Reserved</option><option value="confirmed">Confirmed</option><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select><button class="btn btn-xs btn-primary">Update</button></form></td></tr>
                @empty<tr><td colspan="12"><div class="hm-empty">No conference bookings found.</div></td></tr>@endforelse
            </tbody></table></div>
        </div>
    </div>
</section>
@endsection
