@extends('bankingmicrofinancetreasury::layout')
@section('treasury_content')
<div class="row"><div class="col-md-3"><div class="card"><div class="card-body"><b>Cash On Hand</b><h4>{{ number_format($position['cash_on_hand'],4) }}</h4></div></div></div><div class="col-md-3"><div class="card"><div class="card-body"><b>Available Funding</b><h4>{{ number_format($position['available_funding'],4) }}</h4></div></div></div><div class="col-md-3"><div class="card"><div class="card-body"><b>Net Liquidity</b><h4>{{ number_format($position['net_liquidity'],4) }}</h4></div></div></div></div>
@endsection
