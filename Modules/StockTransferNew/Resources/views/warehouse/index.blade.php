@extends('stocktransfernew::layouts.app')

@section('title', __('stocktransfernew::lang.warehouse_mobile'))
@section('content')
<div class="stn-page stn-mobile-page">
    <div class="stn-command-hero"><h3>@lang('stocktransfernew::lang.warehouse_mobile')</h3><p>@lang('stocktransfernew::lang.mobile_scan_hint')</p></div>
    <div class="stn-kpi-grid">
        <a class="stn-kpi stn-mobile-tile" href="{{ route('stock-transfer-new.warehouse.dispatch') }}"><span>@lang('stocktransfernew::lang.scan_dispatch')</span><strong>→</strong></a>
        <a class="stn-kpi stn-mobile-tile" href="{{ route('stock-transfer-new.warehouse.receive') }}"><span>@lang('stocktransfernew::lang.scan_receive')</span><strong>→</strong></a>
        <a class="stn-kpi stn-mobile-tile" href="{{ route('stock-transfer-new.qr.form') }}"><span>@lang('stocktransfernew::lang.qr_lookup')</span><strong>QR</strong></a>
    </div>
    <div class="stn-card"><h4>@lang('stocktransfernew::lang.open_scan_sessions')</h4>
        <table class="table table-bordered"><thead><tr><th>@lang('stocktransfernew::lang.session_no')</th><th>@lang('stocktransfernew::lang.type')</th><th>@lang('stocktransfernew::lang.started_at')</th><th>@lang('stocktransfernew::lang.action')</th></tr></thead><tbody>
        @forelse($openSessions as $session)<tr><td>{{ $session->session_no }}</td><td>{{ ucfirst($session->scan_type) }}</td><td>{{ $session->started_at }}</td><td><a class="btn btn-primary btn-sm" href="{{ route('stock-transfer-new.warehouse.session',$session->id) }}">@lang('stocktransfernew::lang.continue')</a></td></tr>@empty<tr><td colspan="4" class="text-center">@lang('stocktransfernew::lang.no_data')</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@endsection
