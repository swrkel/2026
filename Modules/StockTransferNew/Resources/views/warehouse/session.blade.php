@extends('stocktransfernew::layouts.app')

@section('title', $session->session_no)
@section('content')
<div class="stn-page stn-mobile-page">
    <div class="stn-card stn-scan-card">
        <h3>{{ $session->session_no }} <small>{{ ucfirst($session->scan_type) }}</small></h3>
        <div class="stn-kpi-grid stn-small-kpi">
            <div class="stn-kpi"><span>@lang('stocktransfernew::lang.expected_qty')</span><strong>{{ number_format($summary['expected_qty'],4) }}</strong></div>
            <div class="stn-kpi"><span>@lang('stocktransfernew::lang.scanned_qty')</span><strong>{{ number_format($summary['scanned_qty'],4) }}</strong></div>
            <div class="stn-kpi"><span>@lang('stocktransfernew::lang.variance_qty')</span><strong>{{ number_format($summary['variance_qty'],4) }}</strong></div>
        </div>
        <form method="POST" action="{{ route('stock-transfer-new.warehouse.scan',$session->id) }}" class="stn-scan-form">@csrf
            <div class="row">
                <div class="col-md-5"><label>@lang('stocktransfernew::lang.barcode')</label><input autofocus class="form-control stn-scan-input" name="barcode" required></div>
                <div class="col-md-2"><label>@lang('stocktransfernew::lang.qty')</label><input class="form-control" name="qty" value="1"></div>
                <div class="col-md-3"><label>@lang('stocktransfernew::lang.batch_no')</label><input class="form-control" name="batch_no"></div>
                <div class="col-md-2"><button class="btn btn-primary stn-big-btn stn-scan-submit">@lang('stocktransfernew::lang.add')</button></div>
            </div>
        </form>
    </div>
    <div class="stn-card"><h4>@lang('stocktransfernew::lang.scanned_lines')</h4>
        <table class="table table-striped"><thead><tr><th>@lang('stocktransfernew::lang.barcode')</th><th>@lang('stocktransfernew::lang.batch_no')</th><th>@lang('stocktransfernew::lang.expected_qty')</th><th>@lang('stocktransfernew::lang.scanned_qty')</th><th>@lang('stocktransfernew::lang.variance_qty')</th></tr></thead><tbody>
        @foreach($lines as $line)<tr><td>{{ $line->barcode }}</td><td>{{ $line->batch_no }}</td><td>{{ number_format($line->expected_qty,4) }}</td><td>{{ number_format($line->scanned_qty,4) }}</td><td>{{ number_format($line->variance_qty,4) }}</td></tr>@endforeach
        </tbody></table>{{ $lines->links() }}
        <form method="POST" action="{{ route('stock-transfer-new.warehouse.submit',$session->id) }}">@csrf<textarea class="form-control" name="remarks" placeholder="@lang('stocktransfernew::lang.remarks')"></textarea><button class="btn btn-success stn-big-btn mt-2">@lang('stocktransfernew::lang.submit_scan_session')</button></form>
    </div>
</div>
@endsection
