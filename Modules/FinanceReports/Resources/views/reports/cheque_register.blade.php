@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }}</h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => request()->url()])
@include('financereports::layouts.toolbar')
@if(!empty($report['message']))<div class="alert alert-warning">{{ $report['message'] }}</div>@endif
<div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Cheque No</th><th>Contact</th><th>Invoice/Ref</th><th>Method</th><th>Status</th><th class="text-right">Amount</th></tr></thead><tbody>
@foreach($report['rows'] ?? [] as $row)<tr><td>{{ $row->cheque_date }}</td><td>{{ $row->cheque_no }}</td><td>{{ $row->contact_name }}</td><td>{{ $row->invoice_no ?: $row->ref_no }}</td><td>{{ $row->method }}</td><td>{{ $row->status }}</td><td class="text-right">{{ number_format($row->amount, 4) }}</td></tr>@endforeach
</tbody><tfoot><tr><th colspan="6">Total / Records: {{ $report['totals']['records'] ?? 0 }}</th><th class="text-right">{{ number_format($report['totals']['amount'] ?? 0, 4) }}</th></tr></tfoot></table>
</div></div></section>
@endsection
