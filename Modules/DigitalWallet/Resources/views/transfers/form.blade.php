@extends('digitalwallet::layout')
@section('digitalwallet-title', 'New Wallet Transfer')
@section('digitalwallet-content')
<form method="POST" action="{{ route('digitalwallet.transfers.store') }}">@csrf
<div class="box box-primary"><div class="box-body">
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="row">
<div class="col-md-4 form-group"><label>From Wallet *</label><select class="form-control" name="from_wallet_id" required><option value="">Select</option>@foreach($wallets as $wallet)<option value="{{ $wallet->id }}">{{ $wallet->wallet_code }} - {{ $wallet->wallet_name }} ({{ number_format($wallet->available_balance, 2) }} {{ $wallet->currency }})</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>To Wallet *</label><select class="form-control" name="to_wallet_id" required><option value="">Select</option>@foreach($wallets as $wallet)<option value="{{ $wallet->id }}">{{ $wallet->wallet_code }} - {{ $wallet->wallet_name }} ({{ $wallet->currency }})</option>@endforeach</select></div>
<div class="col-md-4 form-group"><label>Amount *</label><input type="number" step="0.000001" class="form-control" name="amount" required></div>
<div class="col-md-12 form-group"><label>Note</label><textarea class="form-control" name="note"></textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Transfer</button><a href="{{ route('digitalwallet.transfers.index') }}" class="btn btn-default">Back</a></div></div></form>
@endsection
