@extends('banking-cards::layout')
@section('banking_card_content')
<h3>Debit Card Form</h3>
<form method="post">@csrf
<div class="row"><div class="col-md-4"><label>Customer</label><input class="form-control" name="customer_id"></div><div class="col-md-4"><label>Account</label><input class="form-control" name="deposit_account_id"></div><div class="col-md-4"><label>Masked Card No</label><input class="form-control" name="card_number_masked"></div></div>
<button class="btn btn-primary mt-3">Save</button>
</form>
@endsection
