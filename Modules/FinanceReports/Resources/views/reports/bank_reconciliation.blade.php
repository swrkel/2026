@extends('layouts.app')
@section('title', 'Bank Reconciliation - New')
@section('content')
<section class="content-header"><h1>Bank Reconciliation - New</h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => route('finance-reports.bank-reconciliation-new'), 'asAtMode' => true])
@include('financereports::layouts.toolbar')
@if(!empty($report['message']))<div class="alert alert-warning">{{ $report['message'] }}</div>@endif
<div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-bordered"><tbody>
@foreach($report['summary'] ?? [] as $label=>$amount)<tr><th>{{ ucwords(str_replace('_', ' ', $label)) }}</th><td class="text-right">{{ number_format($amount, 4) }}</td></tr>@endforeach
</tbody></table>
<h4>Bank Accounts</h4><table class="table table-bordered table-striped"><thead><tr><th>Account No</th><th>Account</th><th class="text-right">Book Balance</th></tr></thead><tbody>
@foreach($report['bank_accounts'] ?? [] as $row)<tr><td>{{ $row->account_number }}</td><td>{{ $row->account_name }}</td><td class="text-right">{{ number_format($row->ledger_balance, 4) }}</td></tr>@endforeach
</tbody></table></div></div></section>
@endsection
