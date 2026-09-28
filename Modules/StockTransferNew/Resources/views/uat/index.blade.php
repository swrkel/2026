@extends('layouts.app')
@section('title', __('stocktransfernew::uat.title'))
@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-uat.css') }}">
<section class="stn-uat-wrap">
    <div class="stn-uat-hero">
        <div>
            <h1>{{ __('stocktransfernew::uat.title') }}</h1>
            <p>Final user acceptance checklist for Stock Transfer-New before live usage.</p>
        </div>
        <a href="{{ url('/stock-transfer-new/uat/print') }}" target="_blank" class="btn btn-primary">{{ __('stocktransfernew::uat.print') }}</a>
    </div>
    <div class="stn-uat-summary">
        <div><strong>{{ $summary['total_checks'] }}</strong><span>Total checks</span></div>
        <div><strong>{{ $summary['recommended_pass_rate'] }}%</strong><span>Recommended pass rate</span></div>
        <div><strong>STN_018</strong><span>Support stage</span></div>
    </div>
    @foreach($checklist as $group => $items)
        <div class="stn-uat-card">
            <h3>{{ ucwords(str_replace('_', ' ', $group)) }}</h3>
            <table class="table table-bordered table-striped">
                <thead><tr><th style="width:55%">Check</th><th>Pass</th><th>Fail</th><th>Remarks</th></tr></thead>
                <tbody>
                    @foreach($items as $item)
                        <tr><td>{{ $item }}</td><td></td><td></td><td></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</section>
@endsection
