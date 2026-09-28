@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }}</h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => request()->url(), 'asAtMode' => true])
@include('financereports::layouts.toolbar')
@if(!empty($report['message']))<div class="alert alert-info">{{ $report['message'] }}</div>@endif
<div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Account No</th><th>Account</th><th class="text-right">Ledger Balance</th><th class="text-right">Available Balance</th><th class="text-right">Difference</th></tr></thead><tbody>
@foreach($report['rows'] ?? [] as $row)<tr><td>{{ $row->account_number }}</td><td>{{ $row->account_name }}</td><td class="text-right">{{ number_format($row->ledger_balance, 4) }}</td><td class="text-right">{{ number_format($row->available_balance, 4) }}</td><td class="text-right">{{ number_format($row->difference, 4) }}</td></tr>@endforeach
</tbody><tfoot><tr><th colspan="2">Total</th><th class="text-right">{{ number_format($report['totals']['ledger_balance'] ?? 0, 4) }}</th><th class="text-right">{{ number_format($report['totals']['available_balance'] ?? 0, 4) }}</th><th class="text-right">{{ number_format($report['totals']['difference'] ?? 0, 4) }}</th></tr></tfoot></table>
</div></div></section>
@endsection
