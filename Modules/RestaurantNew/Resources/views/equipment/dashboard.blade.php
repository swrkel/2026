@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::equipment.title'))
@section('content')
<div class="rn-pos-page rn-equipment-page">
    <div class="rn-page-header"><h3>{ __('restaurantnew::equipment.dashboard') }</h3></div>
    <div class="rn-toolbar">
        <input type="text" class="form-control rn-search" placeholder="{ __('restaurantnew::equipment.search') }">
        <button class="btn btn-primary">{ __('restaurantnew::equipment.export') }</button>
        <button class="btn btn-default">{ __('restaurantnew::equipment.print') }</button>
    </div>
    <div class="rn-card-grid">
        <div class="rn-card"><span>Total Assets</span><strong>{{ $totalAssets }}</strong></div>
        <div class="rn-card"><span>Working</span><strong>{{ $workingAssets }}</strong></div>
        <div class="rn-card"><span>Maintenance</span><strong>{{ $maintenanceAssets }}</strong></div>
        <div class="rn-card"><span>Open Work Orders</span><strong>{{ $openWorkOrders }}</strong></div>
        <div class="rn-card"><span>Due Schedules</span><strong>{{ $dueSchedules }}</strong></div>
        <div class="rn-card"><span>Low Spare Parts</span><strong>{{ $lowSpareParts }}</strong></div>
    </div>
</div>
@endsection
