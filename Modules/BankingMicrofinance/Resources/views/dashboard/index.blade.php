@extends('bankingmicrofinance::layouts.app')
@section('page-title','Microfinance Dashboard')
@section('module-content')
<div class="row">
@foreach($summary as $label=>$value)
<div class="col-md-2 col-sm-6"><div class="small-box bg-aqua"><div class="inner"><h3>{{ is_numeric($value) ? number_format($value, 2) : $value }}</h3><p>{{ ucwords(str_replace('_',' ',$label)) }}</p></div></div></div>
@endforeach
</div>
<div class="box"><div class="box-header"><h3 class="box-title">Banking Microfinance Workspace</h3></div><div class="box-body">Use this standalone module for group lending, member registration, loan applications, approval/disbursement, collections, arrears and portfolio reports.</div></div>
@endsection
