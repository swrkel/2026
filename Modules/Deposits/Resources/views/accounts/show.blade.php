@extends('layouts.app')
@section('title', 'Deposit Account')
@section('content')
<section class="content-header no-print"><h1>Deposit Account <small>{{ $account->account_no }}</small></h1></section>
<section class="content no-print">
@include('deposits::layouts.nav')
<div class="row">
    <div class="col-md-7">
        <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Account Summary</h3><div class="box-tools"><a class="btn btn-xs btn-success" href="{{ route('deposits.accounts.statement', $account->id) }}"><i class="fa fa-file-text-o"></i> Statement</a> <a class="btn btn-xs btn-primary" href="{{ route('deposits.certificates.show', $account->id) }}"><i class="fa fa-print"></i> Certificate</a></div></div><div class="box-body"><dl class="dl-horizontal"><dt>Customer</dt><dd>{{ $account->customer_name }}</dd><dt>Product</dt><dd>{{ optional($account->product)->name }}</dd><dt>Principal</dt><dd>{{ number_format($account->principal_amount,2) }}</dd><dt>Balance</dt><dd>{{ number_format($account->current_balance,2) }}</dd><dt>Interest Accrued</dt><dd>{{ number_format($account->interest_accrued,2) }}</dd><dt>Opened On</dt><dd>{{ $account->opened_on }}</dd><dt>Maturity On</dt><dd>{{ $account->maturity_on }}</dd><dt>Certificate No</dt><dd>{{ $account->certificate_no ?: '-' }}</dd><dt>Status</dt><dd>{{ ucfirst($account->status) }}</dd></dl></div></div>
    </div>
    <div class="col-md-5">
        <div class="box box-warning"><div class="box-header with-border"><h3 class="box-title">Lifecycle Actions</h3></div><div class="box-body">
            <form method="POST" action="{{ route('deposits.maturity.close', $account->id) }}" style="display:inline;">@csrf<button class="btn btn-danger" onclick="return confirm('Close this deposit?')"><i class="fa fa-lock"></i> Close</button></form>
            <form method="POST" action="{{ route('deposits.maturity.renew', $account->id) }}" style="display:inline;">@csrf<button class="btn btn-primary" onclick="return confirm('Renew this deposit?')"><i class="fa fa-refresh"></i> Renew</button></form>
            <a href="{{ route('deposits.transactions.create') }}" class="btn btn-success"><i class="fa fa-exchange"></i> Add Transaction</a>
        </div></div>
    </div>
</div>
<div class="box box-info"><div class="box-header with-border"><h3 class="box-title">Nominees / Beneficiaries</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Type</th><th>Name</th><th>Relationship</th><th>NIC</th><th>Mobile</th><th>Share %</th></tr></thead><tbody>@forelse($account->parties as $party)<tr><td>{{ ucfirst($party->party_type) }}</td><td>{{ $party->name }}</td><td>{{ $party->relationship }}</td><td>{{ $party->nic_no }}</td><td>{{ $party->mobile }}</td><td>{{ number_format($party->share_percentage,2) }}</td></tr>@empty<tr><td colspan="6" class="text-center">No parties recorded</td></tr>@endforelse</tbody></table></div></div>
<div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Transactions</h3></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>No</th><th>Type</th><th>Amount</th><th>Balance</th><th>Reference</th><th>Notes</th></tr></thead><tbody>@forelse($account->transactions as $tx)<tr><td>{{ $tx->transaction_date }}</td><td>{{ $tx->transaction_no }}</td><td>{{ ucfirst($tx->type) }}</td><td>{{ number_format($tx->amount,2) }}</td><td>{{ number_format($tx->balance_after,2) }}</td><td>{{ $tx->reference_no }}</td><td>{{ $tx->notes }}</td></tr>@empty<tr><td colspan="7" class="text-center">No transactions found</td></tr>@endforelse</tbody></table></div></div>
</section>
@endsection
