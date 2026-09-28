@extends('stocktransfernew::layouts.app')

@section('title', __('stocktransfernew::lang.qr_lookup'))
@section('content')
<div class="stn-page stn-mobile-page"><div class="stn-card stn-scan-card"><h3>@lang('stocktransfernew::lang.qr_lookup')</h3>
<form method="POST" action="{{ route('stock-transfer-new.qr.lookup') }}">@csrf<label>@lang('stocktransfernew::lang.qr_token')</label><input autofocus class="form-control stn-scan-input" name="qr_token" required><button class="btn btn-primary stn-big-btn mt-2">@lang('stocktransfernew::lang.search')</button></form>
@if(isset($transfer))<hr><h4>{{ $transfer ? $transfer->transfer_no : __('stocktransfernew::lang.no_data') }}</h4>@if($transfer)<p>Status: {{ $transfer->status ?? '' }}</p><p>From: {{ $transfer->from_location_id ?? '' }} / To: {{ $transfer->to_location_id ?? '' }}</p>@endif @endif
</div></div>
@endsection
