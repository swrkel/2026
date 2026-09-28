@extends('bankingcoredeposits::layouts.app')
@section('page-title','Core Deposits Dashboard')
@section('module-content')
<div class="row">
@foreach($summary as $label=>$value)<div class="col-md-3"><div class="bkg-card"><span>{{ ucwords(str_replace('_',' ',$label)) }}</span><strong>{{ number_format((float)$value,4) }}</strong></div></div>@endforeach
</div>
<div class="bkg-panel"><h4>Core Deposits</h4><p>Savings, Current and Fixed Deposit operations are available from the Banking Core Deposits menu.</p></div>
@endsection
