@extends('communicationhub::layout')
@section('communicationhub_title', 'SMS Clients')
@section('communicationhub_content')
<div class="box box-primary"><div class="box-header"><h3 class="box-title">Add SMS Client</h3></div><form method="POST" action="{{ route('communicationhub.commercial.sms_clients.store') }}">@csrf<div class="box-body"><div class="row">
<div class="col-md-3"><label>Client Name</label><input name="name" class="form-control" required></div>
<div class="col-md-3"><label>Business Name</label><input name="business_name" class="form-control"></div>
<div class="col-md-2"><label>Mobile</label><input name="mobile" class="form-control" required></div>
<div class="col-md-2"><label>Email</label><input name="email" type="email" class="form-control"></div>
<div class="col-md-2"><label>Client Type</label><select name="client_type" class="form-control"><option value="business">Business</option><option value="reseller">Reseller</option><option value="external">External Client</option></select></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Client</button></div></form></div>
<div class="box"><div class="box-header"><h3 class="box-title">Clients</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><tr><th>ID</th><th>Name</th><th>Business</th><th>Mobile</th><th>Email</th><th>Type</th><th>Status</th></tr>@forelse($clients as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->name }}</td><td>{{ $row->business_name }}</td><td>{{ $row->mobile }}</td><td>{{ $row->email }}</td><td>{{ $row->client_type }}</td><td>{{ $row->status }}</td></tr>@empty<tr><td colspan="7">No clients yet</td></tr>@endforelse</table></div></div>
@endsection
