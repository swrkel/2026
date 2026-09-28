@extends('layouts.app')

@section('title', __('stocktransfernew::messages.deployment_assistant'))

@section('content')
<section class="content-header stn-pos-header">
    <h1>{{ __('stocktransfernew::messages.deployment_assistant') }}</h1>
</section>
<section class="content stn-deployment-page">
    <div class="row stn-kpi-row">
        <div class="col-md-3"><div class="stn-kpi"><span>Total Checks</span><strong>{{ $summary['total'] }}</strong></div></div>
        <div class="col-md-3"><div class="stn-kpi success"><span>Passed</span><strong>{{ $summary['passed'] }}</strong></div></div>
        <div class="col-md-3"><div class="stn-kpi warning"><span>Warnings</span><strong>{{ $summary['warning'] }}</strong></div></div>
        <div class="col-md-3"><div class="stn-kpi danger"><span>Failed</span><strong>{{ $summary['failed'] }}</strong></div></div>
    </div>

    <div class="box stn-box">
        <div class="box-header with-border">
            <h3 class="box-title">Server Deployment Checks</h3>
            <div class="box-tools">
                <a href="{{ route('stock-transfer-new.deployment.export') }}" class="btn btn-sm btn-primary">CSV</a>
                <a href="{{ route('stock-transfer-new.deployment.sql-tracker') }}" class="btn btn-sm btn-info">SQL Tracker</a>
                <a href="{{ route('stock-transfer-new.deployment.rollback') }}" class="btn btn-sm btn-warning">Rollback Plan</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped stn-table">
                <thead><tr><th>Check</th><th>Status</th><th>Message</th></tr></thead>
                <tbody>
                @foreach($checks as $check)
                    <tr>
                        <td>{{ $check['name'] }}</td>
                        <td><span class="stn-status {{ $check['status'] }}">{{ strtoupper($check['status']) }}</span></td>
                        <td>{{ $check['message'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@push('css')<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stn_041.css') }}">@endpush
@push('javascript')<script src="{{ asset('modules/stocktransfernew/js/stn_041.js') }}"></script>@endpush
