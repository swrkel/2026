@extends('autoservice::layouts.master')
@section('title','Service Package Profitability')
@section('autoservice_content')
<div class="box"><div class="box-header"><h3 class="box-title">Service Package Profitability</h3></div><div class="box-body">
<form method="get" class="form-inline"><input type="date" name="from" class="form-control" value="{{ request('from') }}"><input type="date" name="to" class="form-control" value="{{ request('to') }}"><button class="btn btn-default">Filter</button></form><hr>
<table class="table table-bordered table-striped"><thead><tr><th>Package</th><th>Usage</th><th>Revenue</th><th>Stock Component Value</th><th>Gross Margin</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td>{{ $r->package_name }}</td><td>{{ number_format($r->usage_count) }}</td><td>{{ number_format($r->revenue,2) }}</td><td>{{ number_format($r->component_value,2) }}</td><td>{{ number_format($r->gross_margin,2) }}</td></tr>@empty<tr><td colspan="5" class="text-center">No records found.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div></div>
@endsection
