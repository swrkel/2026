@extends('layouts.app')
@section('title', __('stocktransfernew::logistics.title'))
@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-logistics.css') }}">
<section class="content-header stn-logistics-header">
    <h1>{{ __('stocktransfernew::logistics.title') }}</h1>
    <div class="stn-toolbar">
        <a href="{{ route('stock-transfer-new.logistics.export', request()->all()) }}" class="btn btn-primary btn-sm">CSV</a>
    </div>
</section>
<section class="content stn-logistics-page">
    <div class="row stn-kpis">
        <div class="col-md-2"><div class="stn-card"><span>Total Loads</span><strong>{{ $summary['total_loads'] ?? 0 }}</strong></div></div>
        <div class="col-md-2"><div class="stn-card"><span>Planned</span><strong>{{ $summary['planned_loads'] ?? 0 }}</strong></div></div>
        <div class="col-md-2"><div class="stn-card"><span>Dispatched</span><strong>{{ $summary['dispatched_loads'] ?? 0 }}</strong></div></div>
        <div class="col-md-2"><div class="stn-card"><span>Received</span><strong>{{ $summary['received_loads'] ?? 0 }}</strong></div></div>
        <div class="col-md-2"><div class="stn-card stn-danger"><span>Delayed</span><strong>{{ $summary['delayed_loads'] ?? 0 }}</strong></div></div>
        <div class="col-md-2"><div class="stn-card"><span>Capacity</span><strong>{{ number_format($summary['capacity_qty'] ?? 0, 4) }}</strong></div></div>
    </div>

    <div class="box box-solid stn-panel">
        <div class="box-header with-border"><h3 class="box-title">Create Vehicle Load</h3></div>
        <form method="post" action="{{ route('stock-transfer-new.logistics.loads.store') }}" class="stn-form-grid">
            @csrf
            <input name="transfer_id" class="form-control" placeholder="Transfer ID" required>
            <input name="transfer_no" class="form-control" placeholder="Transfer No">
            <input name="vehicle_no" class="form-control" placeholder="Vehicle No">
            <input name="driver_name" class="form-control" placeholder="Driver Name">
            <input name="driver_mobile" class="form-control" placeholder="Driver Mobile">
            <input name="vehicle_capacity_qty" class="form-control" placeholder="Vehicle Capacity Qty">
            <input name="loaded_qty" class="form-control" placeholder="Loaded Qty">
            <input name="eta_at" type="datetime-local" class="form-control">
            <button class="btn btn-success">Create Load</button>
        </form>
    </div>

    <div class="box box-solid stn-panel">
        <div class="box-header with-border"><h3 class="box-title">Vehicle Loads</h3></div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped stn-table" id="stn-logistics-loads-table">
                <thead><tr><th>Load No</th><th>Transfer</th><th>Vehicle</th><th>Driver</th><th>Status</th><th>Capacity</th><th>Loaded</th><th>ETA</th><th>Route</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($loads as $load)
                    <tr class="{{ $load->eta_at && $load->eta_at < now() && !in_array($load->load_status, ['received','cancelled']) ? 'stn-row-danger' : '' }}">
                        <td>{{ $load->load_no }}</td><td>{{ $load->transfer_no }}</td><td>{{ $load->vehicle_no }}</td><td>{{ $load->driver_name }}</td>
                        <td><span class="label label-info">{{ $load->load_status }}</span></td>
                        <td>{{ number_format($load->vehicle_capacity_qty, 4) }}</td><td>{{ number_format($load->loaded_qty, 4) }}</td><td>{{ $load->eta_at }}</td><td>{{ $load->route_name }}</td>
                        <td class="stn-actions">
                            @if($load->load_status === 'planned')
                                <form method="post" action="{{ route('stock-transfer-new.logistics.loads.dispatch', $load->id) }}">@csrf<button class="btn btn-xs btn-primary">Dispatch</button></form>
                            @endif
                            @if($load->load_status === 'dispatched')
                                <form method="post" action="{{ route('stock-transfer-new.logistics.loads.receive', $load->id) }}">@csrf<button class="btn btn-xs btn-success">Receive</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center">No vehicle loads found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="box box-solid stn-panel">
        <div class="box-header with-border"><h3 class="box-title">Transfer Consolidations</h3></div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped stn-table">
                <thead><tr><th>No</th><th>Date</th><th>Transfers</th><th>Total Qty</th><th>Total Value</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($consolidations as $con)
                    <tr><td>{{ $con->consolidation_no }}</td><td>{{ $con->consolidation_date }}</td><td>{{ $con->transfer_count }}</td><td>{{ number_format($con->total_qty, 4) }}</td><td>{{ number_format($con->total_value, 4) }}</td><td>{{ $con->consolidation_status }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center">No consolidations found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
<script src="{{ asset('modules/stocktransfernew/js/stocktransfernew-logistics.js') }}"></script>
@endsection
