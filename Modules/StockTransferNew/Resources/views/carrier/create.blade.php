@extends('layouts.app')
@section('title', __('carrier.new_invoice'))
@section('content')
<section class="content-header stn-page-header"><h1>{{ __('carrier.new_invoice') }}</h1></section>
<section class="content stn-carrier-page">
    <form method="POST" action="{{ route('stock-transfer-new.carrier-invoices.store') }}" class="box box-solid stn-form-box">
        @csrf
        <div class="box-body row">
            <div class="col-md-3"><label>{{ __('carrier.transfer') }}</label><input name="transfer_id" class="form-control" required></div>
            <div class="col-md-3"><label>{{ __('carrier.invoice_no') }}</label><input name="invoice_no" class="form-control" required></div>
            <div class="col-md-3"><label>{{ __('carrier.invoice_date') }}</label><input name="invoice_date" type="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
            <div class="col-md-3"><label>{{ __('carrier.carrier') }}</label><input name="carrier_name" class="form-control"></div>
            <div class="col-md-2"><label>{{ __('carrier.freight') }}</label><input name="freight_amount" class="form-control input_number" value="0.0000"></div>
            <div class="col-md-2"><label>{{ __('carrier.loading') }}</label><input name="loading_charge" class="form-control input_number" value="0.0000"></div>
            <div class="col-md-2"><label>{{ __('carrier.unloading') }}</label><input name="unloading_charge" class="form-control input_number" value="0.0000"></div>
            <div class="col-md-2"><label>{{ __('carrier.other') }}</label><input name="other_charge" class="form-control input_number" value="0.0000"></div>
            <div class="col-md-2"><label>{{ __('carrier.tax') }}</label><input name="tax_amount" class="form-control input_number" value="0.0000"></div>
            <div class="col-md-2"><label>{{ __('carrier.discount') }}</label><input name="discount_amount" class="form-control input_number" value="0.0000"></div>
            <div class="col-md-12"><label>{{ __('carrier.remarks') }}</label><textarea name="remarks" class="form-control"></textarea></div>
        </div>
        <div class="box-footer"><button class="btn btn-primary">{{ __('carrier.save') }}</button></div>
    </form>
</section>
@endsection
