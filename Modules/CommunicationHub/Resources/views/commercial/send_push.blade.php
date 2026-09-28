@extends('communicationhub::layout')
@section('communicationhub_title', 'Send Push')
@section('communicationhub_content')
@include('communicationhub::partials.professional-styles')
<div class="ch-card"><div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-paper-plane"></i> Send Push Notification</h3><div class="ch-card-subtitle">Queue an immediate browser/mobile push notification.</div></div></div>
<div class="ch-card-body"><form method="POST" action="{{ route('communicationhub.commercial.send_push.store') }}">@csrf
<div class="row"><div class="col-md-4"><div class="form-group"><label>Device</label><select name="device_id" class="form-control"><option value="">All / Manual Recipient</option>@foreach($devices as $d)<option value="{{ $d->id }}">{{ $d->device_name ?? $d->platform ?? 'Device' }} - {{ $d->platform ?? '' }}</option>@endforeach</select></div></div><div class="col-md-4"><div class="form-group"><label>Recipient Token / Group</label><input name="recipient" class="form-control" placeholder="Optional token or group"></div></div><div class="col-md-4"><div class="form-group"><label>Priority</label><select name="priority" class="form-control"><option>normal</option><option>high</option><option>urgent</option></select></div></div></div>
<div class="row"><div class="col-md-6"><div class="form-group"><label>Title</label><input name="title" class="form-control" required></div></div><div class="col-md-6"><div class="form-group"><label>Action URL</label><input name="url" class="form-control" placeholder="https://..."></div></div></div>
<div class="form-group"><label>Message</label><textarea name="message" class="form-control" rows="5" required></textarea></div>
<div class="row"><div class="col-md-6"><div class="form-group"><label>Icon URL</label><input name="icon_url" class="form-control"></div></div><div class="col-md-6"><div class="form-group"><label>Image URL</label><input name="image_url" class="form-control"></div></div></div>
<button class="btn btn-primary"><i class="fa fa-save"></i> Queue Push</button><a href="{{ route('communicationhub.commercial.push_dashboard') }}" class="btn btn-default">Back</a>
</form></div></div>
@endsection
