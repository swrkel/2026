@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::purchase-orders.title'))
@section('content')
<div class="rn-page rn-procurement-page">
    <div class="rn-page-header">
        <h1>Purchase Orders</h1>
        <p>Standalone RestaurantNew procurement and production control.</p>
    </div>
    @include('restaurantnew::components.toolbar', ['module' => 'restaurant-procurement'])
    <div class="rn-card-grid rn-card-grid-4">
        <div class="rn-card"><span>Total</span><strong>0</strong></div>
        <div class="rn-card"><span>Draft</span><strong>0</strong></div>
        <div class="rn-card"><span>Approved</span><strong>0</strong></div>
        <div class="rn-card"><span>Completed</span><strong>0</strong></div>
    </div>
    <div class="rn-panel">
        <table class="table table-bordered table-striped rn-datatable" id="rn-purchase-orders-table">
            <thead>
                <tr>
                    <th>Date</th><th>Reference</th><th>Location</th><th>Status</th><th class="text-right">Amount</th><th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection
