@extends('digitalwallet::layout')
@section('digitalwallet-title', $wallet->exists ? 'Edit Wallet' : 'Add Wallet')
@section('digitalwallet-content')
<form method="POST" action="{{ $wallet->exists ? route('digitalwallet.wallets.update', $wallet) : route('digitalwallet.wallets.store') }}">
    @csrf
    @if($wallet->exists) @method('PUT') @endif
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Wallet Details</h3></div>
        <div class="box-body">
            @if($errors->any())
                <div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <div class="row">
                <div class="col-md-4 form-group"><label>Wallet Name *</label><input class="form-control" name="wallet_name" value="{{ old('wallet_name', $wallet->wallet_name) }}" required></div>
                <div class="col-md-4 form-group"><label>Wallet Type</label>
                    <select class="form-control" name="wallet_type">
                        <option value="general">General Wallet</option>
                        @foreach(($types ?? collect()) as $type)
                            <option value="{{ $type->type_code }}" {{ old('wallet_type', $wallet->wallet_type ?: 'general') == $type->type_code ? 'selected' : '' }}>{{ $type->type_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 form-group"><label>Hierarchy Level</label>
                    <select class="form-control" name="hierarchy_level">
                        @foreach(($levels ?? ['business' => 'Business Wallet']) as $key => $label)
                            <option value="{{ $key }}" {{ old('hierarchy_level', $wallet->hierarchy_level ?: 'business') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 form-group"><label>Parent Wallet</label>
                    <select class="form-control" name="parent_wallet_id">
                        <option value="">None</option>
                        @foreach(($wallets ?? collect()) as $parent)
                            <option value="{{ $parent->id }}" {{ old('parent_wallet_id', $wallet->parent_wallet_id) == $parent->id ? 'selected' : '' }}>{{ $parent->wallet_code }} - {{ $parent->wallet_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 form-group"><label>Currency</label><input class="form-control" name="currency" value="{{ old('currency', $wallet->currency ?: config('digitalwallet.default_currency', 'LKR')) }}"></div>
                <div class="col-md-4 form-group"><label>Status</label><select class="form-control" name="status"><option value="active" {{ old('status', $wallet->status) == 'active' ? 'selected' : '' }}>Active</option><option value="inactive" {{ old('status', $wallet->status) == 'inactive' ? 'selected' : '' }}>Inactive</option></select></div>
            </div>
        </div>
    </div>

    <div class="box box-info">
        <div class="box-header with-border"><h3 class="box-title">Ownership / Scope</h3></div>
        <div class="box-body"><div class="row">
            <div class="col-md-3 form-group"><label>Owner Type</label><input class="form-control" name="owner_type" value="{{ old('owner_type', $wallet->owner_type) }}" placeholder="business / branch / department / user"></div>
            <div class="col-md-3 form-group"><label>Owner ID</label><input type="number" class="form-control" name="owner_id" value="{{ old('owner_id', $wallet->owner_id) }}"></div>
            <div class="col-md-3 form-group"><label>Business ID</label><input type="number" class="form-control" name="business_id" value="{{ old('business_id', $wallet->business_id) }}"></div>
            <div class="col-md-3 form-group"><label>Branch / Location ID</label><input type="number" class="form-control" name="location_id" value="{{ old('location_id', $wallet->location_id) }}"></div>
            <div class="col-md-3 form-group"><label>Department ID</label><input type="number" class="form-control" name="department_id" value="{{ old('department_id', $wallet->department_id) }}"></div>
            <div class="col-md-3 form-group"><label>User ID</label><input type="number" class="form-control" name="user_id" value="{{ old('user_id', $wallet->user_id) }}"></div>
        </div></div>
    </div>

    <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">Balances / Limits</h3></div>
        <div class="box-body"><div class="row">
            @unless($wallet->exists)<div class="col-md-3 form-group"><label>Opening Balance</label><input type="number" step="0.000001" class="form-control" name="available_balance" value="{{ old('available_balance', 0) }}"></div>@endunless
            <div class="col-md-3 form-group"><label>Credit Limit</label><input type="number" step="0.000001" class="form-control" name="credit_limit" value="{{ old('credit_limit', $wallet->credit_limit ?: 0) }}"></div>
            <div class="col-md-3 form-group"><label>Low Balance Threshold</label><input type="number" step="0.000001" class="form-control" name="low_balance_threshold" value="{{ old('low_balance_threshold', $wallet->low_balance_threshold) }}"></div>
            <div class="col-md-3 form-group"><label>Daily Spend Limit</label><input type="number" step="0.000001" class="form-control" name="daily_spend_limit" value="{{ old('daily_spend_limit', $wallet->daily_spend_limit) }}"></div>
            <div class="col-md-3 form-group"><label>Monthly Spend Limit</label><input type="number" step="0.000001" class="form-control" name="monthly_spend_limit" value="{{ old('monthly_spend_limit', $wallet->monthly_spend_limit) }}"></div>
            <div class="col-md-3 form-group"><label>Lock Wallet</label><select class="form-control" name="is_locked"><option value="0" {{ old('is_locked', $wallet->is_locked) ? '' : 'selected' }}>No</option><option value="1" {{ old('is_locked', $wallet->is_locked) ? 'selected' : '' }}>Yes</option></select></div>
            <div class="col-md-6 form-group"><label>Locked Reason</label><input class="form-control" name="locked_reason" value="{{ old('locked_reason', $wallet->locked_reason) }}"></div>
        </div></div>
        <div class="box-footer"><button class="btn btn-primary">Save</button><a href="{{ route('digitalwallet.wallets.index') }}" class="btn btn-default">Back</a></div>
    </div>
</form>
@endsection
