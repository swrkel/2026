@extends('layouts.app')
@section('title', 'Hotel Online Booking Engine')
@section('content')
<section class="content-header hm-page-header">
    <h1><i class="fa fa-globe"></i> Online Booking Engine <small>Direct web bookings, availability, promotions, cancellations and refunds</small></h1>
</section>
<section class="content hm-pos-scope">
    @include('hotelmanagement::partials.nav')
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="row hm-kpi-row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Open Bookings</div><div class="hm-kpi-value">{{ $onlineBooking['open_bookings'] }}</div><div class="hm-kpi-sub">New / confirmed / modified</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Cancelled</div><div class="hm-kpi-value">{{ $onlineBooking['cancelled_bookings'] }}</div><div class="hm-kpi-sub">Controlled cancellations</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Net Revenue</div><div class="hm-kpi-value">{{ number_format($onlineBooking['net_revenue'], 2) }}</div><div class="hm-kpi-sub">After coupon discount</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Advance</div><div class="hm-kpi-value">{{ number_format($onlineBooking['advance_collected'], 2) }}</div><div class="hm-kpi-sub">Online advance captured</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-4"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Promotion / Coupon</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.online-booking.promotion') }}">@csrf
                <div class="form-group"><label>Promo Code</label><input name="promo_code" class="form-control" required placeholder="SUMMER10"></div>
                <div class="form-group"><label>Promo Name</label><input name="promo_name" class="form-control" required></div>
                <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                    <div class="form-group"><label>Discount Type</label><select name="discount_type" class="form-control"><option value="percent">Percent</option><option value="fixed">Fixed Amount</option></select></div>
                    <div class="form-group"><label>Value</label><input type="number" step="0.01" name="discount_value" class="form-control" required></div>
                    <div class="form-group"><label>Valid From</label><input type="date" name="valid_from" class="form-control"></div>
                    <div class="form-group"><label>Valid To</label><input type="date" name="valid_to" class="form-control"></div>
                    <div class="form-group"><label>Active</label><select name="is_active" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Promotion</button></div>
            </form>
        </div></div></div>
        <div class="col-md-8"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Direct Online Booking</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.online-booking.booking') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(4,minmax(120px,1fr))">
                    <div class="form-group"><label>Source</label><input name="booking_source" class="form-control" value="online_engine"></div>
                    <div class="form-group"><label>Guest Name</label><input name="guest_name" class="form-control" required></div>
                    <div class="form-group"><label>Mobile</label><input name="guest_mobile" class="form-control"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="guest_email" class="form-control"></div>
                    <div class="form-group"><label>Arrival</label><input type="date" name="arrival_date" class="form-control" required></div>
                    <div class="form-group"><label>Departure</label><input type="date" name="departure_date" class="form-control" required></div>
                    <div class="form-group"><label>Rooms</label><input type="number" name="rooms" class="form-control" value="1" required></div>
                    <div class="form-group"><label>Adults</label><input type="number" name="adults" class="form-control" value="1"></div>
                    <div class="form-group"><label>Children</label><input type="number" name="children" class="form-control" value="0"></div>
                    <div class="form-group"><label>Room Type ID</label><input type="number" name="room_type_id" class="form-control"></div>
                    <div class="form-group"><label>Rate Plan ID</label><input type="number" name="rate_plan_id" class="form-control"></div>
                    <div class="form-group"><label>Coupon</label><input name="coupon_code" class="form-control"></div>
                    <div class="form-group"><label>Gross Amount</label><input type="number" step="0.01" name="gross_amount" class="form-control" required></div>
                    <div class="form-group"><label>Advance</label><input type="number" step="0.01" name="advance_amount" class="form-control" value="0"></div>
                    <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="new">New</option><option value="confirmed">Confirmed</option><option value="modified">Modified</option></select></div>
                </div>
                <div class="form-group"><label>Remarks</label><textarea name="remarks" rows="2" class="form-control"></textarea></div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-calendar-plus-o"></i> Save Booking</button></div>
            </form>
        </div></div></div>
    </div>

    <div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Booking Calendar Snapshot</h3></div><div class="box-body table-responsive">
        <table class="table table-bordered table-striped hm-table"><thead><tr><th>Arrival</th><th>Departure</th><th>Status</th><th class="text-right">Bookings</th><th class="text-right">Rooms</th></tr></thead><tbody>
        @forelse($onlineBooking['calendar'] as $row)<tr><td>{{ $row->arrival_date }}</td><td>{{ $row->departure_date }}</td><td><span class="label label-info">{{ ucfirst($row->status) }}</span></td><td class="text-right">{{ $row->total_bookings }}</td><td class="text-right">{{ $row->total_rooms }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">No online booking calendar data yet.</td></tr>@endforelse
        </tbody></table>
    </div></div>

    <div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Online Bookings</h3></div><div class="box-body table-responsive">
        <table class="table table-bordered table-striped hm-table"><thead><tr><th>Booking No</th><th>Guest</th><th>Arrival</th><th>Departure</th><th>Rooms</th><th>Coupon</th><th class="text-right">Net</th><th class="text-right">Balance</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        @forelse($onlineBooking['bookings'] as $b)<tr>
            <td>{{ $b->booking_no }}</td><td>{{ $b->guest_name }}<br><small>{{ $b->guest_mobile }} {{ $b->guest_email }}</small></td><td>{{ $b->arrival_date }}</td><td>{{ $b->departure_date }}</td><td>{{ $b->rooms }}</td><td>{{ $b->coupon_code }}</td><td class="text-right">{{ number_format($b->net_amount,2) }}</td><td class="text-right">{{ number_format($b->balance_amount,2) }}</td><td><span class="label label-default">{{ ucfirst($b->status) }}</span></td>
            <td style="min-width:220px">
                <form method="POST" action="{{ route('hotel-management.online-booking.modify', $b->id) }}" class="form-inline" style="display:inline-block">@csrf<input type="hidden" name="status" value="modified"><button class="btn btn-xs hm-btn-edit"><i class="fa fa-pencil"></i> Modify</button></form>
                <form method="POST" action="{{ route('hotel-management.online-booking.cancel', $b->id) }}" class="form-inline" style="display:inline-block">@csrf<button class="btn btn-xs hm-btn-danger"><i class="fa fa-ban"></i> Cancel</button></form>
                <form method="POST" action="{{ route('hotel-management.online-booking.refund', $b->id) }}" class="form-inline" style="display:inline-block">@csrf<input type="hidden" name="refund_amount" value="{{ $b->advance_amount }}"><button class="btn btn-xs hm-btn-print"><i class="fa fa-money"></i> Refund</button></form>
            </td>
        </tr>@empty<tr><td colspan="10" class="text-center text-muted">No online bookings yet.</td></tr>@endforelse
        </tbody></table>
    </div></div>

    <div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Promotions</h3></div><div class="box-body table-responsive">
        <table class="table table-bordered table-striped hm-table"><thead><tr><th>Code</th><th>Name</th><th>Type</th><th class="text-right">Value</th><th>Valid</th><th>Active</th></tr></thead><tbody>
        @forelse($onlineBooking['promotions'] as $p)<tr><td>{{ $p->promo_code }}</td><td>{{ $p->promo_name }}</td><td>{{ ucfirst($p->discount_type) }}</td><td class="text-right">{{ number_format($p->discount_value,2) }}</td><td>{{ $p->valid_from }} - {{ $p->valid_to }}</td><td>{{ $p->is_active ? 'Yes' : 'No' }}</td></tr>@empty<tr><td colspan="6" class="text-center text-muted">No promotions yet.</td></tr>@endforelse
        </tbody></table>
    </div></div>
</section>
@endsection
