@extends('layouts.app')
@section('title', __('distributionnew::mobile.sync_batches'))
@section('content')
<section class="content-header disnew-pos-header"><h1>{{ __('distributionnew::mobile.sync_batches') }}</h1></section>
<section class="content disnew-pos-page">
 <div class="box disnew-pos-card"><div class="box-body table-responsive">
  <table class="table table-bordered table-striped" id="disnew_sync_batches_table">
   <thead><tr><th>ID</th><th>Device</th><th>Direction</th><th>Status</th><th>Payload</th><th>Created</th></tr></thead>
   <tbody>@foreach($batches as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->device_uuid }}</td><td>{{ $row->direction }}</td><td>{{ $row->status }}</td><td>{{ $row->payload_count }}</td><td>{{ $row->created_at }}</td></tr>@endforeach</tbody>
  </table>
 </div></div>
</section>
@endsection
