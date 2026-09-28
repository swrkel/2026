@extends('layouts.app')
@section('title', 'Deposit Certificate')
@section('content')
<section class="content-header no-print"><h1>Deposit Certificate</h1></section>
<section class="content">
<div class="box box-primary">
    <div class="box-header with-border no-print"><a href="{{ route('deposits.accounts.show', $account->id) }}" class="btn btn-default">Back</a> <button onclick="window.print()" class="btn btn-primary">Print</button></div>
    <div class="box-body" style="max-width:900px;margin:auto;font-size:16px;">
        <div class="text-center" style="border:2px solid #333;padding:30px;">
            <h2>Deposit Certificate</h2>
            <h4>Certificate No: {{ $account->certificate_no }}</h4>
            <hr>
            <p>This is to certify that <strong>{{ $account->customer_name }}</strong> holds a deposit account with the following details.</p>
            <table class="table table-bordered" style="margin-top:20px;">
                <tr><th>Account No</th><td>{{ $account->account_no }}</td></tr>
                <tr><th>Product</th><td>{{ optional($account->product)->name }}</td></tr>
                <tr><th>Principal Amount</th><td>{{ number_format($account->principal_amount, 2) }}</td></tr>
                <tr><th>Current Balance</th><td>{{ number_format($account->current_balance, 2) }}</td></tr>
                <tr><th>Interest Rate</th><td>{{ number_format($account->interest_rate, 4) }}%</td></tr>
                <tr><th>Opened On</th><td>{{ $account->opened_on }}</td></tr>
                <tr><th>Maturity On</th><td>{{ $account->maturity_on }}</td></tr>
            </table>
            <br><br>
            <div class="row"><div class="col-xs-6 text-left">Authorized Officer</div><div class="col-xs-6 text-right">Date: {{ date('Y-m-d') }}</div></div>
        </div>
    </div>
</div>
</section>
@endsection
