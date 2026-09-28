@extends('layouts.app')
@section('title', 'Depreciation Register - New')
@section('content')
<section class="content-header"><h1>Depreciation Register - New</h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => request()->url()])
@include('financereports::layouts.toolbar')
<div class="box box-primary"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Asset Code</th><th>Asset Name</th><th>Category</th><th>Branch / Location</th><th>Status</th><th class="text-right">Opening Value</th><th class="text-right">Depreciation</th><th class="text-right">Closing Value</th></tr></thead><tbody>
@foreach($report['rows'] ?? [] as $row)<tr><td>{{ $row->asset_code }}</td><td>{{ $row->asset_name }}</td><td>{{ $row->category }}</td><td>{{ $row->location_name }}</td><td>{{ $row->status }}</td><td class="text-right">{{ number_format($row->opening_value, 4) }}</td><td class="text-right">{{ number_format($row->depreciation, 4) }}</td><td class="text-right">{{ number_format($row->closing_value, 4) }}</td></tr>@endforeach
</tbody><tfoot><tr><th colspan="5">Total</th><th class="text-right">{{ number_format($report['totals']['opening_value'] ?? 0, 4) }}</th><th class="text-right">{{ number_format($report['totals']['depreciation'] ?? 0, 4) }}</th><th class="text-right">{{ number_format($report['totals']['closing_value'] ?? 0, 4) }}</th></tr></tfoot></table></div></div></section>
@endsection
