@extends('bankingmicrofinance::layouts.page')
@section('bkg_mfi_content')
<h4>Cash Handover Variance Report</h4>
<div class="bkg-mfi-toolbar"><input type="text" class="form-control input-sm" placeholder="Date Range"><button class="btn btn-primary btn-sm">CSV</button><button class="btn btn-success btn-sm">Excel</button><button class="btn btn-danger btn-sm">PDF</button><button class="btn btn-default btn-sm">Print</button><button class="btn btn-info btn-sm">Column Visibility</button></div>
<table class="table table-bordered"><thead><tr><th>Date</th><th>Officer</th><th>Center</th><th>Collected</th><th>Verified</th><th>Variance</th></tr></thead><tbody><tr><td colspan="6">Report shell ready for query binding.</td></tr></tbody></table>
@endsection
