@extends('layouts.app')
@section('title', 'Deposit Settings')
@section('content')
<section class="content-header no-print"><h1>Deposit Settings</h1></section>
<section class="content no-print">
@include('deposits::layouts.nav')
<form method="POST" action="{{ route('deposits.settings.update') }}">@csrf
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Numbering & Defaults</h3></div>
    <div class="box-body"><div class="row">
        <div class="col-md-3 form-group"><label>Account Prefix</label><input name="account_prefix" class="form-control" value="{{ old('account_prefix', $settings['account_prefix']) }}"></div>
        <div class="col-md-3 form-group"><label>Certificate Prefix</label><input name="certificate_prefix" class="form-control" value="{{ old('certificate_prefix', $settings['certificate_prefix']) }}"></div>
        <div class="col-md-3 form-group"><label>Transaction Prefix</label><input name="transaction_prefix" class="form-control" value="{{ old('transaction_prefix', $settings['transaction_prefix']) }}"></div>
        <div class="col-md-3 form-group"><label>Interest Frequency</label><select name="default_interest_frequency" class="form-control"><option value="monthly" {{ $settings['default_interest_frequency']=='monthly'?'selected':'' }}>Monthly</option><option value="quarterly" {{ $settings['default_interest_frequency']=='quarterly'?'selected':'' }}>Quarterly</option><option value="maturity" {{ $settings['default_interest_frequency']=='maturity'?'selected':'' }}>At Maturity</option></select></div>
        <div class="col-md-3 form-group"><label>Interest Method</label><select name="default_interest_method" class="form-control"><option value="simple" {{ $settings['default_interest_method']=='simple'?'selected':'' }}>Simple</option><option value="compound" {{ $settings['default_interest_method']=='compound'?'selected':'' }}>Compound</option><option value="flat" {{ $settings['default_interest_method']=='flat'?'selected':'' }}>Flat</option></select></div>
        <div class="col-md-3 form-group"><label>Allow Negative Balance</label><select name="allow_negative_balance" class="form-control"><option value="0" {{ $settings['allow_negative_balance']=='0'?'selected':'' }}>No</option><option value="1" {{ $settings['allow_negative_balance']=='1'?'selected':'' }}>Yes</option></select></div>
        <div class="col-md-3 form-group"><label>Require Nominee by Default</label><select name="require_nominee" class="form-control"><option value="0" {{ $settings['require_nominee']=='0'?'selected':'' }}>No</option><option value="1" {{ $settings['require_nominee']=='1'?'selected':'' }}>Yes</option></select></div>
        <div class="col-md-3 form-group"><label>Require Beneficiary by Default</label><select name="require_beneficiary" class="form-control"><option value="0" {{ $settings['require_beneficiary']=='0'?'selected':'' }}>No</option><option value="1" {{ $settings['require_beneficiary']=='1'?'selected':'' }}>Yes</option></select></div>
    </div></div>
    <div class="box-footer"><button class="btn btn-primary"><i class="fa fa-save"></i> Save Settings</button></div>
</div>
</form>
</section>
@endsection
