@extends('hotelmanagement::layouts.app')
@section('hotel_content')
<section class="content-header"><h1>Reservations <small>Bookings register</small></h1></section>
<section class="content">
@include('hotelmanagement::partials.nav')
@if(session('status'))<div class="alert alert-success hm-alert">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger hm-alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="box hm-card"><div class="box-header"><h3 class="box-title">Add Reservation</h3></div><div class="box-body">
<form method="POST" action="{{ route('hotel-management.reservations.store') }}">@csrf
<div class="hm-form-grid">
<div class="form-group"><label>Reservation No</label><input name="reservation_no" type="text" class="form-control" placeholder="Auto if blank"></div>
<div class="form-group"><label>Guest</label><select name="guest_id" class="form-control"><option value="">Please Select</option>@foreach($guests as $g)<option value="{{ $g->id }}">{{ $g->guest_name }}</option>@endforeach</select></div>
<div class="form-group"><label>Room</label><select name="room_id" class="form-control"><option value="">Please Select</option>@foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->room_no }} - {{ ucfirst($r->status ?? 'available') }}</option>@endforeach</select></div>
<div class="form-group"><label>Rate Plan</label><select name="rate_plan_id" class="form-control"><option value="">Please Select</option>@foreach($ratePlans as $p)<option value="{{ $p->id }}">{{ $p->plan_name }}</option>@endforeach</select></div>
<div class="form-group"><label>Arrival Date</label><input name="arrival_date" type="date" class="form-control" required></div>
<div class="form-group"><label>Departure Date</label><input name="departure_date" type="date" class="form-control" required></div>
<div class="form-group"><label>Adults</label><input name="adults" type="number" class="form-control" value="1"></div>
<div class="form-group"><label>Children</label><input name="children" type="number" class="form-control" value="0"></div>
<div class="form-group"><label>Booking Source</label><input name="booking_source" type="text" class="form-control"></div>
<div class="form-group"><label>Estimated Total</label><input name="estimated_total" type="number" step="0.0001" class="form-control" value="0.0000"></div>
<div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="reserved">Reserved</option><option value="confirmed">Confirmed</option><option value="cancelled">Cancelled</option></select></div>
</div><br><button class="btn hm-btn-add">Save Reservation</button></form></div></div>
<div class="box hm-card"><div class="box-header"><h3 class="box-title">Reservation List</h3></div><div class="box-body">@include('hotelmanagement::partials.toolbar')<div class="table-responsive"><table class="table hm-table table-striped"><thead><tr><th>Reservation No</th><th>Guest</th><th>Rooms</th><th>Arrival</th><th>Departure</th><th>Pax</th><th>Status</th><th>Estimated Total</th></tr></thead><tbody>@forelse($reservations as $row)<tr><td>{{ $row->reservation_no ?? '' }}</td><td>{{ $row->guest_name ?? '-' }}</td><td>{{ $row->room_numbers ?? '-' }}</td><td>{{ $row->arrival_date ?? '' }}</td><td>{{ $row->departure_date ?? '' }}</td><td>{{ ($row->adults ?? 0) }} / {{ ($row->children ?? 0) }}</td><td><span class="hm-badge {{ $row->status ?? '' }}">{{ ucfirst($row->status ?? '-') }}</span></td><td>{{ number_format((float)($row->estimated_total ?? 0), 4) }}</td></tr>@empty<tr><td colspan="8"><div class="hm-empty">No records found.</div></td></tr>@endforelse</tbody></table></div></div></div>
</section>
@endsection
