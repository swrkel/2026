@extends('bankinginternetbanking::layouts.master')
@section('banking_internet_content')
<div class="row">
    @foreach($summary as $label => $value)
        <div class="col-md-2 col-sm-6">
            <div class="box box-solid banking-ib-card">
                <div class="box-body text-center">
                    <div class="ib-number">{{ number_format($value) }}</div>
                    <div class="ib-label">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Internet Banking Enterprise Edition</h3></div>
    <div class="box-body">
        <p>This standalone module contains customer access, account services, beneficiaries, transfers, bill payments, secure messages, security centre, reports, and administration.</p>
        <div class="row">
            <div class="col-md-3"><a class="btn btn-block btn-primary" href="{{ route('banking.internet.access.index') }}">Customer Access</a></div>
            <div class="col-md-3"><a class="btn btn-block btn-primary" href="{{ route('banking.internet.transfers.index') }}">Transfers</a></div>
            <div class="col-md-3"><a class="btn btn-block btn-primary" href="{{ route('banking.internet.bills.index') }}">Bill Payments</a></div>
            <div class="col-md-3"><a class="btn btn-block btn-primary" href="{{ route('banking.internet.reports.index') }}">Reports</a></div>
        </div>
    </div>
</div>
@endsection
