@extends('bankingmicrofinance::layouts.page')
@section('bkg_mfi_content')
<h4>Form</h4>
<form method="post">@csrf
<div class="row"><div class="col-md-3"><label>Date</label><input type="date" name="collection_date" class="form-control"></div><div class="col-md-3"><label>Officer</label><input name="officer_name" class="form-control"></div><div class="col-md-3"><label>Center</label><input name="center_name" class="form-control"></div><div class="col-md-3"><label>Amount</label><input name="amount" class="form-control text-right"></div></div>
<br><button class="btn btn-primary">Save</button>
</form>
@endsection
