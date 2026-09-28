@extends('layouts.app')
@section('title','Pawning Valuation')
@section('content')
<section class="content-header no-print"><h1>Pawning Valuation Calculator</h1></section><section class="content no-print">@include('pawning::layouts.nav')
<form method="POST" action="{{ route('pawning.valuations.calculate') }}">@csrf
<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-2 form-group"><label>Gross Weight</label><input type="number" step="0.0001" name="gross_weight" class="form-control" value="{{ old('gross_weight') }}"></div>
<div class="col-md-2 form-group"><label>Net Weight</label><input type="number" step="0.0001" name="net_weight" class="form-control" value="{{ old('net_weight') }}"></div>
<div class="col-md-2 form-group"><label>Purity / Karat</label><input type="number" step="0.0001" name="purity" class="form-control" value="{{ old('purity',22) }}"></div>
<div class="col-md-3 form-group"><label>Market Rate</label><input type="number" step="0.01" name="market_rate" class="form-control" value="{{ old('market_rate') }}"></div>
<div class="col-md-3 form-group"><label>Advance %</label><input type="number" step="0.0001" name="advance_percentage" class="form-control" value="{{ old('advance_percentage',70) }}"></div>
</div></div><div class="box-footer"><button class="btn btn-primary"><i class="fa fa-calculator"></i> Calculate</button></div></div>
</form>
@if($result)<div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Valuation Result</h3></div><div class="box-body"><div class="row"><div class="col-md-4"><h4>Assessed Value</h4><h3>{{ number_format($result['assessed_value'],2) }}</h3></div><div class="col-md-4"><h4>Advance Amount</h4><h3>{{ number_format($result['advance_amount'],2) }}</h3></div><div class="col-md-4"><h4>Purity Factor</h4><h3>{{ number_format($result['purity_factor'],4) }}</h3></div></div></div></div>@endif
</section>@endsection
