@extends('layouts.app')
@section('title', __('stocktransfernew::lang.cache_control'))
@section('content')
<section class="content-header"><h1>@lang('stocktransfernew::lang.cache_control')</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="box box-solid"><div class="box-body">
<form method="POST" action="{{ route('stock-transfer-new.performance.cache-control.warm') }}" style="display:inline">@csrf<button class="btn btn-success">Warm Cache</button></form>
<form method="POST" action="{{ route('stock-transfer-new.performance.cache-control.clear') }}" style="display:inline">@csrf<button class="btn btn-danger">Clear Cache</button></form>
</div></div>
<div class="box box-solid"><div class="box-body"><pre>{{ json_encode($summary, JSON_PRETTY_PRINT) }}</pre></div></div>
</section>
@endsection
