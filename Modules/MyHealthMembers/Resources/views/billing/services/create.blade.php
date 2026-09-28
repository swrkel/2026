@extends('layouts.app')
@section('title', 'Add Billing Service')
@section('content')
<section class="content-header"><h1>Add Billing Service</h1></section>
<section class="content"><form method="post" action="{{ route('myhealth.billing.services.store') }}">@csrf
<div class="row"><div class="col-md-3"><label>Service Code</label><input name="service_code" class="form-control" required></div><div class="col-md-5"><label>Service Name</label><input name="service_name" class="form-control" required></div><div class="col-md-2"><label>Type</label><select name="service_type" class="form-control"><option value="consultation">Consultation</option><option value="pharmacy">Pharmacy</option><option value="lab">Lab</option><option value="telemedicine">Telemedicine</option><option value="other">Other</option></select></div><div class="col-md-2"><label>Amount</label><input name="default_amount" class="form-control text-right" value="0.0000"></div></div><br>
<label>Description</label><textarea name="description" class="form-control"></textarea><br><label><input type="checkbox" name="is_active" value="1" checked> Active</label><br><button class="btn btn-primary">Save</button> <a href="{{ route('myhealth.billing.services.index') }}" class="btn btn-default">Cancel</a>
</form></section>
@endsection
