@extends('layouts.app')
@section('title', $title ?? 'Bank Guarantees')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'Bank Guarantees' }}</h1></section>
<section class="content">
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">{{ $title ?? 'Bank Guarantees' }}</h3></div>
<div class="box-body">
<div class="btn-toolbar bkg-toolbar" style="margin-bottom:15px"><button class="btn btn-primary btn-sm">Search</button> <button class="btn btn-default btn-sm">Date Range</button> <button class="btn btn-success btn-sm">Excel</button> <button class="btn btn-info btn-sm">CSV</button> <button class="btn btn-danger btn-sm">PDF</button> <button class="btn btn-default btn-sm">Print</button> <button class="btn btn-warning btn-sm">Column Visibility</button></div>
<p>This standalone Trade Finance page is ready for tester navigation and future transaction workflows.</p>
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Reference</th><th>Customer/Party</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead><tbody><tr><td colspan="6" class="text-center">No records yet</td></tr></tbody></table>
</div></div>
</section>
@endsection
