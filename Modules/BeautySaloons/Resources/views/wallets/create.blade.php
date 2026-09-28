@extends('beautysaloons::layout')
@section('beauty_content')
<div class="bs-page">
    <h3>Create Wallet</h3>
    <form method="POST" action="{{ route('beauty-saloons.wallets.store') }}" class="bs-form-card">
        @csrf
        <div class="row">
            <div class="col-md-4"><label>Customer ID</label><input type="text" name="customer_id" class="form-control"></div>
            <div class="col-md-4"><label>Customer Name</label><input type="text" name="customer_name" class="form-control" required></div>
            <div class="col-md-4"><label>Mobile</label><input type="text" name="customer_mobile" class="form-control"></div>
            <div class="col-md-4"><label>Opening Balance</label><input type="text" name="opening_balance" class="form-control input_number" value="0.00"></div>
            <div class="col-md-4"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="blocked">Blocked</option></select></div>
            <div class="col-md-12"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
        </div>
        <div class="text-right mt-3"><button class="btn btn-success bs-big-save">Save Wallet</button></div>
    </form>
</div>
@endsection
