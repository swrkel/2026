@extends('layouts.app')
@section('title', 'Asset Valuation Report - New')
@section('content')
<section class="content-header"><h1>Asset Valuation Report - New</h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => request()->url(), 'asAtMode' => true])
@include('financereports::layouts.toolbar')
<div class="box box-primary"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Asset Code</th><th>Asset Name</th><th>Category</th><th>Branch / Location</th><th class="text-right">Purchase Cost</th><th class="text-right">Accumulated Depreciation</th><th class="text-right">Current Book Value</th><th>Status</th></tr></thead><tbody>
@foreach($report['rows'] ?? [] as $row)<tr><td>{{ $row->asset_code }}</td><td>{{ $row->asset_name }}</td><td>{{ $row->category }}</td><td>{{ $row->location_name }}</td><td class="text-right">{{ number_format($row->cost, 4) }}</td><td class="text-right">{{ number_format($row->depreciation, 4) }}</td><td class="text-right">{{ number_format($row->book_value, 4) }}</td><td>{{ $row->status }}</td></tr>@endforeach
</tbody><tfoot><tr><th colspan="4">Total</th><th class="text-right">{{ number_format($report['totals']['cost'] ?? 0, 4) }}</th><th class="text-right">{{ number_format($report['totals']['depreciation'] ?? 0, 4) }}</th><th class="text-right">{{ number_format($report['totals']['book_value'] ?? 0, 4) }}</th><th></th></tr></tfoot></table></div></div></section>
@endsection
