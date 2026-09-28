@extends('autoservice::layouts.master')
@section('title','Package Stock Consumption')
@section('autoservice_content')
<div class="box"><div class="box-header"><h3 class="box-title">Package Stock Consumption</h3></div><div class="box-body">
<form method="get" class="form-inline"><input type="date" name="from" class="form-control" value="{{ request('from') }}"><input type="date" name="to" class="form-control" value="{{ request('to') }}"><button class="btn btn-default">Filter</button></form><hr>
<table class="table table-bordered table-striped"><thead><tr><th>Package</th><th>Stock Item</th><th>Qty Used</th><th>Sales Value</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td>{{ $r->package_name }}</td><td>{{ $r->product_name }}</td><td>{{ number_format($r->quantity_used,3) }}</td><td>{{ number_format($r->sales_value,2) }}</td></tr>@empty<tr><td colspan="4" class="text-center">No records found.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div></div>
@endsection
