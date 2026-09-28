@extends('communicationhub::layout')
@section('communicationhub_title', 'SMS Packages')
@section('communicationhub_content')
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if(!empty($schemaMissing))<div class="alert alert-danger"><strong>SMS Packages table could not be prepared automatically.</strong> You may still submit the form; if it fails, check the Laravel log and tenant database CREATE/ALTER permissions.</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="box box-primary"><div class="box-header"><h3 class="box-title">Add SMS Package</h3></div><form method="POST" action="{{ route('communicationhub.commercial.sms_packages.store') }}">@csrf<div class="box-body"><div class="row">
<div class="col-md-3"><label>Package Name</label><input name="name" class="form-control" required></div>
<div class="col-md-2"><label>Credits</label><input name="credits" type="number" class="form-control" required></div>
<div class="col-md-2"><label>Cost Price</label><input name="cost_price" type="number" step="0.01" class="form-control" value="0"></div>
<div class="col-md-2"><label>Selling Price</label><input name="selling_price" type="number" step="0.01" class="form-control" required></div>
<div class="col-md-2"><label>Validity Days</label><input name="validity_days" type="number" class="form-control" value="0"></div>
<div class="col-md-12"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Package</button></div></form></div>
<div class="box"><div class="box-header"><h3 class="box-title">SMS Packages</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><tr><th>ID</th><th>Name</th><th>Credits</th><th>Cost</th><th>Selling</th><th>Profit</th><th>Status</th></tr>@forelse($packages as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->name }}</td><td>{{ $row->credits }}</td><td>{{ number_format((float)$row->cost_price,2) }}</td><td>{{ number_format((float)$row->selling_price,2) }}</td><td>{{ number_format((float)$row->profit_amount,2) }}</td><td>{{ !empty($row->is_active) ? 'Active' : 'Inactive' }}</td></tr>@empty<tr><td colspan="7">No packages yet</td></tr>@endforelse</table></div></div>
@endsection
