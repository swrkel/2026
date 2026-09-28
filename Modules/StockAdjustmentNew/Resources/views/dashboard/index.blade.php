@extends('stockadjustmentnew::layouts.app')

@section('san_title', 'Stock Adjustment Command Center')

@section('san_content')
@php
    $qtyDecimals = (int) ($settings['quantity_decimals'] ?? 4);
    $amountDecimals = (int) ($settings['amount_decimals'] ?? 4);
@endphp
<div class="san-grid">
    @foreach($stats as $label => $value)
        <div class="san-card">
            <span>{{ ucwords(str_replace('_', ' ', $label)) }}</span>
            <strong>{{ is_numeric($value) ? number_format((float) $value, $label === 'total_cost' ? $amountDecimals : $qtyDecimals) : $value }}</strong>
        </div>
    @endforeach
</div>

<div class="san-panel">
    <h3>Latest Adjustments</h3>
    <div class="table-responsive">
        <table class="table table-bordered table-striped" data-san-dashboard-table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Qty</th>
                    <th>Cost</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($latest as $row)
                    <tr>
                        <td>{{ $row->adjustment_no }}</td>
                        <td>{{ optional($row->adjustment_date)->format('Y-m-d') }}</td>
                        <td><span class="san-status">{{ $row->status }}</span></td>
                        <td class="text-right">{{ number_format((float) $row->total_qty, $qtyDecimals) }}</td>
                        <td class="text-right">{{ number_format((float) $row->total_cost_amount, $amountDecimals) }}</td>
                        <td>
                            <a href="{{ route('stock-adjustment-new.adjustments.show', $row) }}">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($latest->isEmpty())
        <div class="san-table-empty">No stock adjustments have been created yet.</div>
    @endif
</div>
@endsection
