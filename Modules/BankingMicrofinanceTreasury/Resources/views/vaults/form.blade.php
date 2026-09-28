@extends('bankingmicrofinancetreasury::layout')
@section('treasury_content')
<form method="post" action="#">@csrf
<div class="row"><div class="col-md-4"><label>Reference</label><input class="form-control" name="reference"></div><div class="col-md-4"><label>Amount / Balance</label><input class="form-control" name="amount" type="number" step="0.0001"></div><div class="col-md-4"><label>Status</label><select class="form-control" name="status"><option>draft</option><option>active</option><option>submitted</option></select></div></div>
<button class="btn btn-primary mt-3">Save</button></form>
@endsection
