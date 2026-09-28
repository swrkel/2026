@extends('hotelmanagement::layouts.app')
@section('hotel_content')
<section class="content-header"><h1>Hotel Management Dashboard <small>POS standard layout</small></h1></section>
<section class="content">
@include('hotelmanagement::partials.nav')
@if(session('status'))<div class="alert alert-success hm-alert">{{ session('status') }}</div>@endif
<div class="row">
@foreach(['hotels'=>'Hotels','rooms'=>'Rooms','available_rooms'=>'Available Rooms','reservations'=>'Reservations','arrivals_today'=>'Today Arrivals','departures_today'=>'Today Departures','open_folios'=>'Open Folios','dirty_rooms'=>'Dirty Rooms'] as $key=>$label)
<div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">{{ $label }}</div><div class="hm-kpi-value">{{ number_format($counts[$key] ?? 0) }}</div><div class="hm-kpi-sub">Current business / location</div></div></div>
@endforeach
</div>
<div class="row"><div class="col-md-6"><div class="box"><div class="box-header"><h3 class="box-title">Latest Reservations</h3></div><div class="box-body">@include('hotelmanagement::partials.toolbar')<div class="table-responsive"><table class="table hm-table table-striped"><thead><tr><th>No</th><th>Arrival</th><th>Departure</th><th>Status</th><th>Total</th></tr></thead><tbody>@forelse($recentReservations as $r)<tr><td>{{ $r->reservation_no ?? $r->id }}</td><td>{{ $r->arrival_date ?? '' }}</td><td>{{ $r->departure_date ?? '' }}</td><td><span class="hm-badge {{ $r->status ?? '' }}">{{ ucfirst($r->status ?? '-') }}</span></td><td>{{ number_format($r->estimated_total ?? 0, 4) }}</td></tr>@empty<tr><td colspan="5"><div class="hm-empty">No reservation records found.</div></td></tr>@endforelse</tbody></table></div></div></div></div><div class="col-md-6"><div class="box"><div class="box-header"><h3 class="box-title">Room Status</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table table-striped"><thead><tr><th>Room</th><th>Name</th><th>Status</th><th>HK</th></tr></thead><tbody>@forelse($recentRooms as $r)<tr><td>{{ $r->room_no }}</td><td>{{ $r->room_name }}</td><td><span class="hm-badge {{ $r->status }}">{{ ucfirst($r->status) }}</span></td><td><span class="hm-badge {{ $r->housekeeping_status }}">{{ ucfirst($r->housekeeping_status) }}</span></td></tr>@empty<tr><td colspan="4"><div class="hm-empty">No rooms found.</div></td></tr>@endforelse</tbody></table></div></div></div></div></div></section>
@endsection
