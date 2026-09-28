@extends('layouts.app')

@section('content')
<section class="content-header stn-support-header"><h1>{{ __('stocktransfernew::lang.production_support') }}</h1></section>
<section class="content stn-support-page">

@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Safe Repair Utilities</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Repair</th><th>Risk</th><th>Action</th></tr></thead><tbody>
@foreach($repairs as $repair)
<tr><td>{{ $repair['title'] }}</td><td>{{ $repair['risk'] }}</td><td><form method="POST" action="{{ route('stock-transfer-new.support.repair.run') }}">@csrf<input type="hidden" name="repair_key" value="{{ $repair['key'] }}"><button class="btn btn-primary btn-sm stn-support-repair-btn" type="submit">Run</button></form></td></tr>
@endforeach
</tbody></table></div></div>
</section>
@endsection
