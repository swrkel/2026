@extends('digitalwallet::layout')
@section('digitalwallet-title', 'Digital Wallet Settings')
@section('digitalwallet-content')
<form method="POST" action="{{ route('digitalwallet.settings.store') }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-4 form-group"><label>Default Currency</label><input class="form-control" name="default_currency" value="{{ $settings['default_currency'] ?? config('digitalwallet.default_currency', 'LKR') }}"></div>
<div class="col-md-4 form-group"><label>Low Balance Threshold</label><input type="number" class="form-control" name="low_balance_threshold" value="{{ $settings['low_balance_threshold'] ?? config('digitalwallet.low_balance_threshold', 1000) }}"></div>
<div class="col-md-4 form-group"><label>Allow Negative Balance</label><select class="form-control" name="allow_negative_balance"><option value="0">No</option><option value="1" {{ ($settings['allow_negative_balance'] ?? 0) == 1 ? 'selected' : '' }}>Yes</option></select></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save Settings</button></div></div>
</form>
@endsection
