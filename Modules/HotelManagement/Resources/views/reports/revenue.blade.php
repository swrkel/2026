@extends('hotelmanagement::layouts.app')
@section('hotel_content')
<section class="content-header"><h1>Revenue / ADR / RevPAR <small>POS standard layout</small></h1></section>
<section class="content">
@include('hotelmanagement::partials.nav')
<div class="box"><div class="box-header"><h3 class="box-title">Filters</h3></div><div class="box-body">@include('hotelmanagement::reports.partials.filters')</div></div>
<div class="row">
@foreach(['room_revenue'=>'Room Revenue','payments'=>'Guest Payments','balance'=>'Balance','adr'=>'ADR','revpar'=>'RevPAR'] as $key=>$label)
<div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">{{ $label }}</div><div class="hm-kpi-value">{{ number_format($summary[$key] ?? 0, 4) }}</div><div class="hm-kpi-sub">Selected period</div></div></div>
@endforeach
</div>
</section>
@endsection
