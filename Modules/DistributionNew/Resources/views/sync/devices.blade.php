@extends('layouts.app')
@section('title', __('distributionnew::mobile.devices'))
@section('content')
<section class="content-header disnew-pos-header"><h1>{{ __('distributionnew::mobile.devices') }}</h1></section>
<section class="content disnew-pos-page">
 <div class="box disnew-pos-card"><div class="box-body table-responsive">
  <table class="table table-bordered table-striped" id="disnew_devices_table">
   <thead><tr><th>ID</th><th>Device</th><th>User</th><th>Platform</th><th>Status</th><th>Last Seen</th></tr></thead>
   <tbody>@foreach($devices as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->device_name }}</td><td>{{ $row->user_id }}</td><td>{{ $row->platform }}</td><td>{{ $row->status }}</td><td>{{ $row->last_seen_at }}</td></tr>@endforeach</tbody>
  </table>
 </div></div>
</section>
@endsection
