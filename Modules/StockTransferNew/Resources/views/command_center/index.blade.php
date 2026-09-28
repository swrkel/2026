@extends('stocktransfernew::layouts.app')
@section('stocktransfernew_content')
@include('stocktransfernew::partials.header',['title'=>'Transfer Command Center','subtitle'=>'Live operational overview for requests, approvals, dispatch, receive and exceptions'])
<div class="stn-action-row">
    <a class="stn-btn stn-btn-primary" href="{{ route('stock-transfer-new.transfers.create') }}">Create Transfer</a>
    <a class="stn-btn" href="{{ route('stock-transfer-new.command-center.queue') }}">Operational Queue</a>
    <a class="stn-btn" href="{{ route('stock-transfer-new.approvals.index') }}">Approvals</a>
    <a class="stn-btn" href="{{ route('stock-transfer-new.dispatch.index') }}">Dispatch</a>
    <a class="stn-btn" href="{{ route('stock-transfer-new.receive.index') }}">Receive</a>
</div>
<div class="stn-kpi-grid stn-kpi-grid-wide">
@foreach($kpis as $label=>$value)
    <div class="stn-kpi"><span>{{ ucwords(str_replace('_',' ',$label)) }}</span><strong>{{ number_format($value) }}</strong></div>
@endforeach
</div>
<div class="stn-command-grid">
    @include('stocktransfernew::command_center.panel',['title'=>'Pending Approval','items'=>$pending_approval,'empty'=>'No transfer requests waiting for approval.','action'=>'stock-transfer-new.approvals.index'])
    @include('stocktransfernew::command_center.panel',['title'=>'Approved / Waiting Dispatch','items'=>$approved_waiting_dispatch,'empty'=>'No approved transfers waiting for dispatch.','action'=>'stock-transfer-new.dispatch.index'])
    @include('stocktransfernew::command_center.panel',['title'=>'In Transit','items'=>$in_transit,'empty'=>'No stock currently in transit.','action'=>'stock-transfer-new.receive.index'])
    @include('stocktransfernew::command_center.panel',['title'=>'Delayed / Attention Required','items'=>$delayed,'empty'=>'No delayed transfers.','action'=>'stock-transfer-new.command-center.queue'])
    @include('stocktransfernew::command_center.panel',['title'=>'Variance Transfers','items'=>$variance,'empty'=>'No shortage/excess variance found.','action'=>'stock-transfer-new.reconciliation.index'])
    <div class="stn-card">
        <div class="stn-card-header"><strong>Recent Activity</strong></div>
        <div class="stn-card-body stn-activity-list">
            @forelse($recent_activity as $row)
                <div class="stn-activity-item"><strong>{{ $row->transfer_no }}</strong><span>{{ ucwords(str_replace('_',' ',$row->action ?? 'activity')) }}</span><small>{{ $row->created_at }}</small></div>
            @empty
                <p class="stn-muted">No recent transfer activity.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
