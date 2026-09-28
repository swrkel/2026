@extends('stocktransfernew::layouts.app')

@section('title', __('stocktransfernew::lang.scan_receive'))
@section('content')
<div class="stn-page stn-mobile-page">
    <div class="stn-card stn-scan-card">
        <h3>@lang('stocktransfernew::lang.scan_receive')</h3>
        <form method="POST" action="{{ route('stock-transfer-new.warehouse.open-session') }}">@csrf
            <input type="hidden" name="scan_type" value="receive">
            <div class="row">
                <div class="col-md-4"><label>@lang('stocktransfernew::lang.transfer_id')</label><input class="form-control" name="transfer_id" placeholder="Transfer ID / Document ID"></div>
                <div class="col-md-4"><label>@lang('stocktransfernew::lang.location')</label><input class="form-control" name="location_id" placeholder="Location ID"></div>
                <div class="col-md-4"><label>@lang('stocktransfernew::lang.store')</label><input class="form-control" name="store_id" placeholder="Store ID"></div>
            </div>
            <button class="btn btn-success stn-big-btn mt-3">@lang('stocktransfernew::lang.start_scan')</button>
        </form>
    </div>
</div>
@endsection
