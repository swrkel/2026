@extends('distributionnew::layouts.app')
@section('content')
<div class="pos-card disnew-page"><div class="pos-card-header"><h4>SMS Officer Groups</h4></div><div class="pos-card-body">
<form method="POST" action="{{ route('distribution-new.settings.sms-officer-groups.save') }}">@csrf
<div class="row"><div class="col-md-4"><label>Event</label><select name="event_key" class="form-control"><option value="sales_order_created">Sales Order Created</option><option value="sales_invoice_created">Sales Invoice Created</option><option value="loading_completed">Loading Completed</option><option value="return_created">Return Created</option><option value="credit_note_created">Credit Note Created</option></select></div><div class="col-md-4"><label>Group Name</label><input name="group_name" class="form-control"></div><div class="col-md-4"><label>Officer Mobiles</label><input name="officer_mobile_numbers" class="form-control" placeholder="Comma separated"></div></div>
<button class="btn btn-primary mt-3">Save Officer Group</button></form></div></div>
@endsection
