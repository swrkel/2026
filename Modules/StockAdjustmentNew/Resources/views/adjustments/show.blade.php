@extends('stockadjustmentnew::layouts.app')

@section('san_title', 'Stock Adjustment ' . $adjustment->adjustment_no)

@section('san_content')
@php
    $qtyDecimals = (int) ($settings['quantity_decimals'] ?? 4);
    $amountDecimals = (int) ($settings['amount_decimals'] ?? 4);
    $hostTransactionIds = (array) (($adjustment->posting_summary['host_transaction_ids'] ?? []) ?: []);
@endphp
<div class="san-panel">
    <div class="row">
        <div class="col-md-2"><strong>Date:</strong> {{ optional($adjustment->adjustment_date)->format('Y-m-d') }}</div>
        <div class="col-md-2"><strong>Type:</strong> {{ ucwords(str_replace('_', ' ', $adjustment->adjustment_type)) }}</div>
        <div class="col-md-2"><strong>Overall Adjustment:</strong> {{ ucfirst($adjustment->stock_adjustment_type ?: 'increase') }}</div>
        <div class="col-md-2"><strong>Status:</strong> <span class="san-status">{{ $adjustment->status }}</span></div>
        <div class="col-md-4"><strong>Location / Store:</strong> {{ $locationName ?: ($adjustment->location_id ?: '—') }} / {{ $storeName ?: ($adjustment->store_id ?: 'All / no store') }}</div>
    </div>
</div>

<div class="san-panel">
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Batch Number</th>
                    <th>Adjustment Type</th>
                    <th>Expiry Date</th>
                    <th>System</th>
                    <th>Counted</th>
                    <th>Adjustment</th>
                    <th>Cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach($adjustment->lines as $line)
                    <tr>
                        <td>{{ $line->product_name ?: $line->product_id }}</td>
                        <td>{{ $line->sku ?: '—' }}</td>
                        <td>{{ $line->batch_no ?: '—' }}</td>
                        <td>{{ ucfirst($line->stock_adjustment_type ?: ((float) $line->adjustment_qty >= 0 ? 'increase' : 'decrease')) }}</td>
                        <td>{{ optional($line->expiry_date)->format('Y-m-d') ?: '—' }}</td>
                        <td class="text-right">{{ number_format((float) $line->system_qty, $qtyDecimals) }}</td>
                        <td class="text-right">{{ number_format((float) $line->counted_qty, $qtyDecimals) }}</td>
                        <td class="text-right">{{ number_format((float) $line->adjustment_qty, $qtyDecimals) }}</td>
                        <td class="text-right">{{ number_format((float) $line->cost_amount, $amountDecimals) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($adjustment->status === 'posted')
    <div class="alert alert-success san-posting-summary">
        <strong>Posted successfully.</strong>
        Stock quantities and mapped accounts were updated.
        @if(!empty($hostTransactionIds))
            Host Transactions:
            @foreach($hostTransactionIds as $direction => $transactionId)
                {{ ucfirst($direction) }} #{{ $transactionId }}@unless($loop->last), @endunless
            @endforeach.
        @elseif($adjustment->host_transaction_id)
            Host Transaction ID: {{ $adjustment->host_transaction_id }}.
        @endif
    </div>
@endif

<div class="san-actions-inline">
    @if($adjustment->status === 'draft')
        <form method="POST" action="{{ route('stock-adjustment-new.adjustments.submit', $adjustment) }}">
            @csrf
            <button class="btn btn-info" type="submit">{{ (bool) ($settings['require_approval'] ?? true) ? 'Submit' : 'Approve & Continue' }}</button>
        </form>
    @endif

    @if($adjustment->status === 'submitted')
        <form method="POST" action="{{ route('stock-adjustment-new.adjustments.approve', $adjustment) }}">
            @csrf
            <button class="btn btn-success" type="submit">Approve</button>
        </form>
        <form method="POST" action="{{ route('stock-adjustment-new.adjustments.reject', $adjustment) }}">
            @csrf
            <button class="btn btn-danger" type="submit">Reject</button>
        </form>
    @endif

    @if($adjustment->status === 'approved')
        <form method="POST" action="{{ route('stock-adjustment-new.adjustments.post', $adjustment) }}">
            @csrf
            <button class="btn btn-primary" type="submit">Approved &amp; Post</button>
        </form>
    @endif
</div>
@endsection
