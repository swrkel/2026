@extends('customers::layouts.action', ['title' => 'Loan to Customer'])
@section('customer_action_body')
<form method="POST" action="{{ route('customers.loans.store', $customer->id) }}">
    {{ csrf_field() }}
    <style>
.customer-payment-section-card{background:#f8fbff;border:1px solid #dce9f7;border-radius:14px;padding:16px;margin-bottom:15px;}
.customer-payment-section-card label{font-weight:700;color:#1f2d3d;}
.customer-payment-section-card .input-group-addon{cursor:pointer;background:#eef6ff;}
</style>
<div class="row customer-payment-section-card">
        <div class="col-md-4"><div class="form-group"><label>Customer</label><input class="form-control" value="{{ $customer->name }}" readonly></div></div>
        <div class="col-md-4"><div class="form-group"><label>Current Balance Due</label><input class="form-control text-right" value="{{ number_format((float)($totalDue ?? 0), 2) }}" readonly></div></div>
        <div class="col-md-4"><div class="form-group"><label>Loan Amount</label><input name="amount" class="form-control input_number text-right" required></div></div>
        <div class="col-md-4"><div class="form-group"><label>Transaction Date</label><div class="input-group"><span class="input-group-addon customers-open-datepicker"><i class="fa fa-calendar"></i></span><input name="date" class="form-control datepicker customers-action-datepicker" data-provide="datepicker" value="{{ $today ?? date('m/d/Y') }}" autocomplete="off" required></div></div></div>
        <div class="col-md-4"><div class="form-group"><label>Payment Method</label>{!! Form::select('method', $paymentMethods ?? ['cash' => 'Cash'], old('method', 'cash'), ['class' => 'form-control select2', 'required']) !!}</div></div>
        <div class="col-md-4"><div class="form-group"><label>Payment Account</label>{!! Form::select('account_id', ['' => 'Please Select'] + ($accountOptions ?? []), old('account_id', $defaultAccountId ?? null), ['class' => 'form-control select2', 'required']) !!}</div></div>
        <div class="col-md-12"><div class="form-group"><label>Note</label><textarea name="note" class="form-control"></textarea></div></div>
    </div>
    <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Save Loan</button>
</form>
@endsection
