@extends('stocktransfernew::layouts.app')
@section('title', __('stocktransfernew::delivery.detail'))
@section('content')
<div class="stn-page stn-delivery-detail">
    <div class="stn-card">
        <h3>{{ __('stocktransfernew::delivery.detail') }} #{{ $delivery->id }}</h3>
        <p><strong>Transfer:</strong> {{ $delivery->transfer_id }}</p>
        <p><strong>Delivered:</strong> {{ optional($delivery->delivered_at)->format('Y-m-d H:i') }}</p>
        <p><strong>Receiver:</strong> {{ $delivery->received_by }} {{ $delivery->receiver_mobile ? '(' . $delivery->receiver_mobile . ')' : '' }}</p>
        <p><strong>Condition:</strong> {{ ucfirst($delivery->condition_status) }}</p>
        <p><strong>Remarks:</strong> {{ $delivery->remarks }}</p>
    </div>
    <div class="stn-card mt-3">
        <h4>{{ __('stocktransfernew::delivery.damage_notes') }}</h4>
        <table class="table table-bordered table-sm">
            <thead><tr><th>Product</th><th>Qty</th><th>Type</th><th>Value</th><th>Remarks</th></tr></thead>
            <tbody>
            @forelse($delivery->damages as $damage)
                <tr><td>{{ $damage->product_id }}</td><td>{{ number_format($damage->qty, 3) }}</td><td>{{ $damage->damage_type }}</td><td>{{ number_format($damage->estimated_value, 4) }}</td><td>{{ $damage->remarks }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-center">No damages recorded</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
