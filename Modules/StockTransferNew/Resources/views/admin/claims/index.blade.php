@extends('stocktransfernew::layouts.app')
@section('title', __('stocktransfernew::claims.title'))
@section('content')
<div class="stn-claims-page">
    <div class="stn-page-header">
        <div><h3>{{ __('stocktransfernew::claims.title') }}</h3><p>Claim, recover and close transfer shortages, damages and excess variances.</p></div>
        <a href="{{ route('stock-transfer-new.claims.create') }}" class="btn btn-primary">{{ __('stocktransfernew::claims.create') }}</a>
    </div>
    <div class="stn-kpi-grid">
        <div class="stn-kpi"><span>Total Claims</span><strong>{{ number_format($summary['total_claims']) }}</strong></div>
        <div class="stn-kpi"><span>Open Claims</span><strong>{{ number_format($summary['open_claims']) }}</strong></div>
        <div class="stn-kpi"><span>Claimed Value</span><strong>{{ number_format($summary['claimed_value'], 2) }}</strong></div>
        <div class="stn-kpi"><span>Outstanding</span><strong>{{ number_format($summary['claimed_value'] - $summary['recovered_value'] - $summary['writeoff_value'], 2) }}</strong></div>
    </div>
    <form method="get" class="stn-toolbar">
        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        <select name="status"><option value="">All Status</option>@foreach(['draft','submitted','approved','closed','cancelled'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
        <select name="claim_type"><option value="">All Claim Types</option>@foreach(['variance','shortage','damage','excess'] as $type)<option value="{{ $type }}" @selected(($filters['claim_type'] ?? '') === $type)>{{ ucfirst($type) }}</option>@endforeach</select>
        <button class="btn btn-secondary">Search</button>
    </form>
    <div class="table-responsive stn-card">
        <table class="table table-bordered table-sm stn-table">
            <thead><tr><th>Claim No</th><th>Date</th><th>Transfer</th><th>Type</th><th>Status</th><th class="text-right">Claimed</th><th class="text-right">Recovered</th><th class="text-right">Outstanding</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($claims as $claim)
                <tr>
                    <td>{{ $claim->claim_no }}</td><td>{{ $claim->claim_date }}</td><td>{{ $claim->transfer_no }}</td><td>{{ ucfirst($claim->claim_type) }}</td><td><span class="stn-badge stn-badge-{{ $claim->claim_status }}">{{ ucfirst($claim->claim_status) }}</span></td>
                    <td class="text-right">{{ number_format($claim->claimed_value, 2) }}</td><td class="text-right">{{ number_format($claim->recovered_value, 2) }}</td><td class="text-right">{{ number_format($claim->outstanding_value, 2) }}</td>
                    <td><a href="{{ route('stock-transfer-new.claims.show', $claim->id) }}" class="btn btn-xs btn-info">View</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted">No claims found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
