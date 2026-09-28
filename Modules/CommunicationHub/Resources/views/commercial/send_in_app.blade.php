@extends('communicationhub::layout')
@section('communicationhub_title', 'Send In-App Alert')
@section('communicationhub_content')
@include('communicationhub::partials.professional-styles')
<div class="ch-card"><div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-paper-plane"></i> Send In-App Notification</h3><div class="ch-card-subtitle">Create an ERP inbox alert for a user, role, group, location or the current business.</div></div></div>
<div class="ch-card-body"><form method="POST" action="{{ route('communicationhub.commercial.send_in_app.store') }}">@csrf
<div class="row"><div class="col-md-3"><div class="form-group"><label>User ID</label><input name="recipient_user_id" class="form-control" placeholder="Optional"></div></div><div class="col-md-3"><div class="form-group"><label>Role</label><input name="recipient_role" class="form-control" placeholder="manager / cashier"></div></div><div class="col-md-3"><div class="form-group"><label>Group</label><input name="recipient_group" class="form-control" placeholder="all / branch-users"></div></div><div class="col-md-3"><div class="form-group"><label>Priority</label><select name="priority" class="form-control"><option>normal</option><option>high</option><option>urgent</option></select></div></div></div>
<div class="row"><div class="col-md-6"><div class="form-group"><label>Title</label><input name="title" class="form-control" required></div></div><div class="col-md-3"><div class="form-group"><label>Type</label><select name="notification_type" class="form-control"><option value="info">Info</option><option value="alert">Alert</option><option value="approval">Approval</option><option value="reminder">Reminder</option><option value="system">System</option></select></div></div><div class="col-md-3"><div class="form-group"><label>Expires At</label><input type="datetime-local" name="expires_at" class="form-control"></div></div></div>
<div class="form-group"><label>Message</label><textarea name="message" class="form-control" rows="5" required></textarea></div>
<div class="form-group"><label>Action URL</label><input name="action_url" class="form-control" placeholder="/sales/invoices or https://..."></div>
<button class="btn btn-primary"><i class="fa fa-save"></i> Create Notification</button><a href="{{ route('communicationhub.commercial.in_app_dashboard') }}" class="btn btn-default">Back</a>
</form></div></div>
@endsection
