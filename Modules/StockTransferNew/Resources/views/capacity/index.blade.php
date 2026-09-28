@extends('stocktransfernew::layouts.app')
@section('content')
<div class="stn-page stn-capacity-page">
    <div class="stn-header"><h3>{{ __('stocktransfernew::messages.capacity_planning') }}</h3></div>
    <div class="stn-kpi-row">
        <div class="stn-kpi"><span>Total</span><strong>{{ $summary['total_transfers'] }}</strong></div>
        <div class="stn-kpi"><span>Over Capacity</span><strong>{{ $summary['over_capacity'] }}</strong></div>
        <div class="stn-kpi"><span>Critical</span><strong>{{ $summary['critical'] }}</strong></div>
        <div class="stn-kpi"><span>Unassigned Vehicle</span><strong>{{ $summary['unassigned_vehicle'] }}</strong></div>
    </div>
    <div class="stn-card"><a class="btn btn-success" href="{{ route('stock-transfer-new.capacity.export', request()->query()) }}">CSV</a></div>
    <div class="stn-card">
        <table class="table table-bordered stn-table">
            <thead><tr><th>Transfer</th><th>Priority</th><th>Status</th><th>Vehicle</th><th>ETA</th><th>Used</th><th>Limit</th></tr></thead>
            <tbody>@foreach($plans as $row)<tr class="{{ ((float)($row->capacity_used ?? 0) > (float)($row->capacity_limit ?? 0)) ? 'stn-danger-row' : '' }}"><td>{{ $row->transfer_no }}</td><td>{{ ucfirst($row->priority ?? 'normal') }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ $row->vehicle_id ?: '-' }}</td><td>{{ $row->eta_at ?: '-' }}</td><td>{{ number_format((float)$row->capacity_used,3) }}</td><td>{{ number_format((float)$row->capacity_limit,3) }}</td></tr>@endforeach</tbody>
        </table>
        {{ $plans->links() }}
    </div>
</div>
@endsection
