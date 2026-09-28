@extends('digitalwallet::layout')
@section('digitalwallet-title', $rule->exists ? 'Edit Rule' : 'Add Rule')
@section('digitalwallet-content')
<form method="POST" action="{{ $rule->exists ? route('digitalwallet.rules.update', $rule) : route('digitalwallet.rules.store') }}">
@csrf @if($rule->exists) @method('PUT') @endif
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-4 form-group"><label>Rule Name *</label><input class="form-control" name="rule_name" value="{{ old('rule_name', $rule->rule_name) }}" required></div>
<div class="col-md-4 form-group"><label>Rule Type *</label><input class="form-control" name="rule_type" value="{{ old('rule_type', $rule->rule_type ?: 'minimum_balance') }}" required></div>
<div class="col-md-4 form-group"><label>Wallet Type</label><input class="form-control" name="wallet_type" value="{{ old('wallet_type', $rule->wallet_type) }}"></div>
<div class="col-md-3 form-group"><label>Minimum Balance</label><input type="number" step="0.000001" class="form-control" name="minimum_balance" value="{{ old('minimum_balance', $rule->minimum_balance) }}"></div>
<div class="col-md-3 form-group"><label>Maximum Balance</label><input type="number" step="0.000001" class="form-control" name="maximum_balance" value="{{ old('maximum_balance', $rule->maximum_balance) }}"></div>
<div class="col-md-3 form-group"><label>Daily Limit</label><input type="number" step="0.000001" class="form-control" name="daily_limit" value="{{ old('daily_limit', $rule->daily_limit) }}"></div>
<div class="col-md-3 form-group"><label>Monthly Limit</label><input type="number" step="0.000001" class="form-control" name="monthly_limit" value="{{ old('monthly_limit', $rule->monthly_limit) }}"></div>
<div class="col-md-3 checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $rule->is_active ?? true) ? 'checked' : '' }}> Active</label></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save</button><a href="{{ route('digitalwallet.rules.index') }}" class="btn btn-default">Back</a></div></div>
</form>
@endsection
