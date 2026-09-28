@extends('bankingmicrofinance::bkg_mfi_006.layouts.page')
@section('title','Credit Committee Report')
@section('module_content')
<div class="bkg-mfi-toolbar"><input class="form-control" placeholder="Search"><input class="form-control" placeholder="Date Range"><button class="btn btn-primary">CSV</button><button class="btn btn-primary">Excel</button><button class="btn btn-primary">PDF</button><button class="btn btn-primary">Print</button><button class="btn btn-primary">Column Visibility</button></div>
<div class="card"><div class="card-header"><h4>Credit Committee Report</h4></div><div class="card-body"><p>Enterprise lending and credit management standalone page.</p><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Reference</th><th>Customer / Member</th><th>Stage / Status</th><th>Score / Amount</th><th>Action</th></tr></thead><tbody><tr><td colspan="6" class="text-center text-muted">No records loaded yet.</td></tr></tbody></table></div></div>
@endsection
