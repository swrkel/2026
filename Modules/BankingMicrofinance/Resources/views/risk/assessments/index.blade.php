@extends('bankingmicrofinance::layouts.page')
@section('title','Risk Assessment Register')
@section('content')
<div class="bkg-mfi-toolbar"><input class="form-control" placeholder="Search"><input type="text" class="form-control" placeholder="Date Range"><button class="btn btn-primary">CSV</button><button class="btn btn-primary">Excel</button><button class="btn btn-primary">PDF</button><button class="btn btn-primary">Print</button><button class="btn btn-primary">Column Visibility</button></div>
<div class="card"><div class="card-header"><h4>Risk Assessment Register</h4></div><div class="card-body"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Reference</th><th>Member</th><th>Status</th><th>Amount / Score</th><th>Action</th></tr></thead><tbody><tr><td colspan="6" class="text-center text-muted">No records loaded yet.</td></tr></tbody></table></div></div>
@endsection
