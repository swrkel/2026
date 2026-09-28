@extends('beautysaloons::layouts.app')
@section('title', __('beautysaloons::finance.account_mappings'))
@section('content')
<section class="content-header"><h1>@lang('beautysaloons::finance.account_mappings')</h1></section>
<section class="content">
<form method="POST" action="{{ route('beauty-saloons.finance.mappings.save') }}">
@csrf
<div class="box box-primary"><div class="box-body">
@foreach(['service_income','product_income','cash_account','card_account','bank_account','wallet_liability','voucher_liability','commission_payable','discount_allowed','refund_account'] as $key)
<div class="form-group col-md-6"><label>{{ ucwords(str_replace('_',' ', $key)) }}</label><input name="mappings[{{ $key }}]" class="form-control" placeholder="Account ID"></div>
@endforeach
</div><div class="box-footer text-right"><button class="btn btn-primary btn-lg">@lang('messages.save')</button></div></div>
</form>
</section>
@endsection
