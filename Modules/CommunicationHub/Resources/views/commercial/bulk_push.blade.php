@extends('communicationhub::layout')
@section('communicationhub_title', 'Bulk Push')
@section('communicationhub_content')
@include('communicationhub::partials.professional-styles')
<div class="ch-card"><div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-bullhorn"></i> Bulk Push Notifications</h3><div class="ch-card-subtitle">Queue push notifications to multiple tokens or a target device group.</div></div></div>
<div class="ch-card-body"><form method="POST" action="{{ route('communicationhub.commercial.bulk_push.store') }}">@csrf
<div class="row"><div class="col-md-6"><div class="form-group"><label>Target Group</label><input name="target_group" class="form-control" placeholder="all-active-devices / customers / employees"></div></div><div class="col-md-6"><div class="form-group"><label>Priority</label><select name="priority" class="form-control"><option>normal</option><option>high</option><option>urgent</option></select></div></div></div>
<div class="form-group"><label>Recipient Tokens</label><textarea name="recipients" class="form-control" rows="5" placeholder="One token per line. Leave blank to use target group."></textarea></div>
<div class="form-group"><label>Title</label><input name="title" class="form-control" required></div><div class="form-group"><label>Message</label><textarea name="message" class="form-control" rows="5" required></textarea></div><div class="form-group"><label>Action URL</label><input name="url" class="form-control"></div>
<button class="btn btn-primary"><i class="fa fa-save"></i> Queue Bulk Push</button></form></div></div>
@endsection
