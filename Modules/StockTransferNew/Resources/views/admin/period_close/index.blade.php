@extends('layouts.app')
@section('title', __('stocktransfernew::period_close.title'))
@section('content')
<section class="content-header stn-pc-header">
    <h1>{{ __('stocktransfernew::period_close.title') }}</h1>
    <p>{{ __('stocktransfernew::period_close.subtitle') }}</p>
</section>
<section class="content stn-pc-page">
    <form method="GET" class="stn-pc-filter">
        <input type="number" name="year" value="{{ $year }}" min="2020" max="2100">
        <input type="number" name="month" value="{{ $month }}" min="1" max="12">
        <input type="number" name="location_id" value="{{ $locationId }}" placeholder="Location ID">
        <input type="number" name="store_id" value="{{ $storeId }}" placeholder="Store ID">
        <button class="btn btn-primary">Preview</button>
    </form>

    <div class="stn-pc-grid">
        <div class="stn-pc-card"><span>Total Transfers</span><strong>{{ $preview['summary']['total_transfers'] }}</strong></div>
        <div class="stn-pc-card"><span>Pending</span><strong>{{ $preview['summary']['pending_transfers'] }}</strong></div>
        <div class="stn-pc-card"><span>In Transit</span><strong>{{ $preview['summary']['in_transit_transfers'] }}</strong></div>
        <div class="stn-pc-card"><span>Open Variance</span><strong>{{ $preview['summary']['variance_transfers'] }}</strong></div>
    </div>

    <div class="box stn-pc-box">
        <div class="box-header with-border"><h3 class="box-title">Close Readiness</h3></div>
        <div class="box-body">
            @if(count($preview['issues']))
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Severity</th><th>Issue</th><th>Message</th><th>Action Required</th></tr></thead>
                    <tbody>
                    @foreach($preview['issues'] as $issue)
                        <tr>
                            <td><span class="stn-severity stn-{{ $issue['severity'] }}">{{ strtoupper($issue['severity']) }}</span></td>
                            <td>{{ $issue['issue_type'] }}</td>
                            <td>{{ $issue['issue_message'] }}</td>
                            <td>{{ $issue['action_required'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-success">This period is ready to close for StockTransfer-New.</div>
            @endif
        </div>
        <div class="box-footer">
            <form method="POST" action="{{ route('stocktransfernew.admin.period-close.lock') }}">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="location_id" value="{{ $locationId }}">
                <input type="hidden" name="store_id" value="{{ $storeId }}">
                <textarea name="remarks" class="form-control" placeholder="Close remarks / admin note"></textarea>
                <button class="btn btn-success stn-pc-lock">Lock / Validate Period</button>
            </form>
        </div>
    </div>

    <div class="box stn-pc-box">
        <div class="box-header with-border"><h3 class="box-title">Recent Period Close Records</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Period</th><th>Status</th><th>Total</th><th>Pending</th><th>Transit</th><th>Variance</th><th>Locked At</th></tr></thead>
                <tbody>
                @forelse($latestCloses as $close)
                    <tr>
                        <td>{{ $close->period_year }}-{{ str_pad($close->period_month, 2, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ strtoupper($close->status) }}</td>
                        <td>{{ $close->total_transfers }}</td>
                        <td>{{ $close->pending_transfers }}</td>
                        <td>{{ $close->in_transit_transfers }}</td>
                        <td>{{ $close->variance_transfers }}</td>
                        <td>{{ optional($close->locked_at)->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No period close records yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
@push('css')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-period-close.css') }}">
@endpush
