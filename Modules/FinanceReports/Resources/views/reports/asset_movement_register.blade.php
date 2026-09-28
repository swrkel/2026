@extends('layouts.app')
@section('title', $title ?? 'Asset Movement Register - New')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'Asset Movement Register - New' }}</h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => request()->url()])
@include('financereports::layouts.toolbar')
<div class="box box-primary"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Asset Code</th><th>Asset Name</th><th>Movement Type</th><th>From Branch</th><th>To Branch</th><th class="text-right">Amount</th><th>Remarks</th></tr></thead><tbody>
@foreach($report['rows'] ?? [] as $row)<tr><td>{{ $row->date }}</td><td>{{ $row->asset_code }}</td><td>{{ $row->asset_name }}</td><td>{{ $row->movement_type }}</td><td>{{ $row->from_location }}</td><td>{{ $row->to_location }}</td><td class="text-right">{{ number_format($row->amount, 4) }}</td><td>{{ $row->remarks }}</td></tr>@endforeach
</tbody><tfoot><tr><th colspan="6">Total / Record Count: {{ $report['totals']['records'] ?? 0 }}</th><th class="text-right">{{ number_format($report['totals']['amount'] ?? 0, 4) }}</th><th></th></tr></tfoot></table></div></div></section>
@endsection
