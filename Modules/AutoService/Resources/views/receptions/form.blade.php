@extends('autoservice::layouts.master')
@section('content')
@include('autoservice::layouts.nav')
<section class="content-header"><h1>New Vehicle Reception</h1></section>
<section class="content"><form method="POST" action="{{ route('autoservice.receptions.store') }}">@csrf
<div class="box"><div class="box-body"><div class="row">
 <div class="col-md-4"><label>Vehicle *</label><input name="vehicle_id" class="form-control" required placeholder="Vehicle ID or select via search"></div>
 <div class="col-md-4"><label>Customer</label><input name="contact_id" class="form-control" placeholder="Customer ID"></div>
 <div class="col-md-4"><label>Received At</label><input name="received_at" type="datetime-local" class="form-control"></div>
 <div class="col-md-3"><label>Odometer</label><input name="odometer" type="number" class="form-control"></div>
 <div class="col-md-3"><label>Fuel Level</label><select name="fuel_level" class="form-control"><option value="">Please Select</option><option>Empty</option><option>1/4</option><option>1/2</option><option>3/4</option><option>Full</option></select></div>
 <div class="col-md-6"><label>Accessories Received</label><input name="accessories_received" class="form-control" placeholder="Jack, spare wheel, documents, etc."></div>
 <div class="col-md-6"><label>Customer Complaint</label><textarea name="customer_complaint" class="form-control" rows="4"></textarea></div>
 <div class="col-md-6"><label>Advisor Remarks</label><textarea name="advisor_remarks" class="form-control" rows="4"></textarea></div>
 <div class="col-md-12"><label>Existing Damage / Notes</label><textarea name="existing_damage" class="form-control" rows="3"></textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Reception</button><a href="{{ route('autoservice.receptions.index') }}" class="btn btn-default">Cancel</a></div></div></form></section>
@endsection
