@extends('layouts.app')
@section('title', 'Asset Category Summary - New')
@section('content')
<section class="content-header"><h1>Asset Category Summary - New</h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => request()->url(), 'asAtMode' => true])
@include('financereports::layouts.toolbar')
<div class="box box-primary"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Category</th><th class="text-right">Assets</th><th class="text-right">Cost</th><th class="text-right">Accumulated Depreciation</th><th class="text-right">Book Value</th></tr></thead><tbody>
@foreach($report['rows'] ?? [] as $row)<tr><td>{{ $row->category }}</td><td class="text-right">{{ $row->records }}</td><td class="text-right">{{ number_format($row->cost, 4) }}</td><td class="text-right">{{ number_format($row->depreciation, 4) }}</td><td class="text-right">{{ number_format($row->book_value, 4) }}</td></tr>@endforeach
</tbody><tfoot><tr><th>Total</th><th class="text-right">{{ $report['totals']['records'] ?? 0 }}</th><th class="text-right">{{ number_format($report['totals']['cost'] ?? 0, 4) }}</th><th class="text-right">{{ number_format($report['totals']['depreciation'] ?? 0, 4) }}</th><th class="text-right">{{ number_format($report['totals']['book_value'] ?? 0, 4) }}</th></tr></tfoot></table></div></div></section>
@endsection
