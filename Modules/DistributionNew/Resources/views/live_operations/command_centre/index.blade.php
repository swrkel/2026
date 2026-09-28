@extends('distributionnew::layouts.app')
@section('title', __('distributionnew::live.command_centre'))
@section('content')
<div class="disnew-pos-dashboard">
 <div class="row disnew-kpi-row">
  <div class="col-md-3"><div class="disnew-pos-card"><span>Vehicles on Road</span><h3 id="vehicles_on_road">0</h3></div></div>
  <div class="col-md-3"><div class="disnew-pos-card"><span>Active Drivers</span><h3 id="drivers_active">0</h3></div></div>
  <div class="col-md-3"><div class="disnew-pos-card"><span>Pending Deliveries</span><h3 id="deliveries_pending">0</h3></div></div>
  <div class="col-md-3"><div class="disnew-pos-card"><span>Delayed</span><h3 id="deliveries_delayed">0</h3></div></div>
 </div>
 <div class="box"><div class="box-header with-border"><h3 class="box-title">Live Operations Timeline</h3></div><div class="box-body"><table class="table table-bordered table-striped" id="disnew_live_timeline_table"><thead><tr><th>Time</th><th>Vehicle</th><th>Driver</th><th>Event</th><th>Status</th></tr></thead></table></div></div>
</div>
@endsection
@section('javascript')<script src="{{ asset('modules/distributionnew/js/live_operations/command_centre.js') }}"></script>@endsection
