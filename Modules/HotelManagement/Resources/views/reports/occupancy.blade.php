@extends('hotelmanagement::layouts.app')
@section('hotel_content')
<section class="content-header"><h1>Occupancy Report <small>POS standard layout</small></h1></section>
<section class="content">
@include('hotelmanagement::partials.nav')
<div class="box"><div class="box-header"><h3 class="box-title">Filters</h3></div><div class="box-body">@include('hotelmanagement::reports.partials.filters')</div></div>
<div class="row">
@foreach(['total_rooms'=>'Total Rooms','available_room_nights'=>'Available Room Nights','occupied_room_nights'=>'Occupied Room Nights','occupancy_percent'=>'Occupancy %'] as $key=>$label)
<div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">{{ $label }}</div><div class="hm-kpi-value">{{ number_format($summary[$key] ?? 0, $key === 'occupancy_percent' ? 2 : 0) }}</div><div class="hm-kpi-sub">Selected period</div></div></div>
@endforeach
</div>
</section>
@endsection
