@extends('customers::portal.layout')
@section('title', 'Dealer Analytics')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    <div class="dd-card">
        <div class="dd-card-header">
            <h3 class="dd-card-title">Dealer Analytics Dashboard</h3>
        </div>
        <div class="dd-card-body">
            <p style="margin:0;color:#64748b;font-weight:700;">{{ $customer->name }} @if(!empty($customer->contact_id)) | {{ $customer->contact_id }} @endif</p>
        </div>
    </div>

    <div class="dd-summary">
        <div class="dd-summary-item"><div class="dd-summary-label">Current Outstanding</div><div class="dd-summary-value">{{ number_format($summary['current_outstanding'] ?? 0, 2) }}</div></div>
        <div class="dd-summary-item"><div class="dd-summary-label">Credit Limit</div><div class="dd-summary-value">{{ number_format($summary['credit_limit'] ?? 0, 2) }}</div></div>
        <div class="dd-summary-item"><div class="dd-summary-label">Available Credit</div><div class="dd-summary-value">{{ number_format($summary['available_credit'] ?? 0, 2) }}</div></div>
        <div class="dd-summary-item"><div class="dd-summary-label">This Month Purchases</div><div class="dd-summary-value">{{ number_format($summary['this_month_purchases'] ?? 0, 2) }}</div></div>
        <div class="dd-summary-item"><div class="dd-summary-label">This Month Payments</div><div class="dd-summary-value">{{ number_format($summary['this_month_payments'] ?? 0, 2) }}</div></div>
    </div>

    @php
        $util = (float) ($summary['credit_utilization'] ?? 0);
        $utilClass = $util <= 60 ? 'dd-aging-good' : ($util <= 85 ? 'dd-aging-warning' : 'dd-aging-danger');
        $maxPurchase = max(array_map(function($r){ return (float) $r['amount']; }, $purchaseTrend ?: [['amount'=>0]]));
        $maxPayment = max(array_map(function($r){ return (float) $r['amount']; }, $paymentTrend ?: [['amount'=>0]]));
        $maxProduct = max(array_map(function($r){ return (float) $r['amount']; }, $topProducts ?: [['amount'=>0]]));
    @endphp

    <div class="row">
        <div class="col-md-6">
            <div class="dd-card">
                <div class="dd-card-header"><h3 class="dd-card-title">Credit Utilization</h3></div>
                <div class="dd-card-body">
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <span class="dd-aging {{ $utilClass }}">{{ number_format($util, 2) }}%</span>
                        <div style="flex:1;min-width:220px;background:#e5edf6;border-radius:999px;height:18px;overflow:hidden;">
                            <div style="width:{{ min($util, 100) }}%;height:18px;background:linear-gradient(135deg,#2563eb,#06b6d4);"></div>
                        </div>
                    </div>
                    <p style="margin-top:12px;color:#64748b;font-weight:700;">Used credit is calculated from current outstanding against approved credit limit.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dd-card">
                <div class="dd-card-header"><h3 class="dd-card-title">Performance Snapshot</h3></div>
                <div class="dd-card-body">
                    <table class="dd-table" style="min-width:0;">
                        <tr><td>Total Purchases</td><td class="text-right">{{ number_format($performance['total_purchases'] ?? 0, 2) }}</td></tr>
                        <tr><td>Total Payments</td><td class="text-right">{{ number_format($performance['total_payments'] ?? 0, 2) }}</td></tr>
                        <tr><td>Average Monthly Purchase</td><td class="text-right">{{ number_format($performance['average_monthly_purchase'] ?? 0, 2) }}</td></tr>
                        <tr><td>Average Monthly Payment</td><td class="text-right">{{ number_format($performance['average_monthly_payment'] ?? 0, 2) }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Monthly Purchase Trend - Last 12 Months</h3></div>
        <div class="dd-card-body dd-table-wrap">
            <table class="dd-table">
                <thead><tr><th>Month</th><th class="text-right">Purchase Amount</th><th class="text-right">Purchase Quantity</th><th>Trend</th></tr></thead>
                <tbody>
                    @foreach($purchaseTrend as $row)
                        @php $width = $maxPurchase > 0 ? min(((float)$row['amount'] / $maxPurchase) * 100, 100) : 0; @endphp
                        <tr>
                            <td>{{ $row['month'] }}</td>
                            <td class="text-right">{{ number_format($row['amount'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['quantity'], 3) }}</td>
                            <td><div style="background:#e5edf6;border-radius:999px;height:12px;"><div style="width:{{ $width }}%;height:12px;border-radius:999px;background:#2563eb;"></div></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Monthly Payment Trend - Last 12 Months</h3></div>
        <div class="dd-card-body dd-table-wrap">
            <table class="dd-table">
                <thead><tr><th>Month</th><th class="text-right">Payment Amount</th><th>Trend</th></tr></thead>
                <tbody>
                    @foreach($paymentTrend as $row)
                        @php $width = $maxPayment > 0 ? min(((float)$row['amount'] / $maxPayment) * 100, 100) : 0; @endphp
                        <tr>
                            <td>{{ $row['month'] }}</td>
                            <td class="text-right">{{ number_format($row['amount'], 2) }}</td>
                            <td><div style="background:#e5edf6;border-radius:999px;height:12px;"><div style="width:{{ $width }}%;height:12px;border-radius:999px;background:#22c55e;"></div></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="dd-card">
                <div class="dd-card-header"><h3 class="dd-card-title">Top Purchased Products</h3></div>
                <div class="dd-card-body dd-table-wrap">
                    @if(count($topProducts))
                        <table class="dd-table">
                            <thead><tr><th>Product</th><th class="text-right">Quantity</th><th class="text-right">Amount</th><th>Share</th></tr></thead>
                            <tbody>
                                @foreach($topProducts as $row)
                                    @php $width = $maxProduct > 0 ? min(((float)$row['amount'] / $maxProduct) * 100, 100) : 0; @endphp
                                    <tr>
                                        <td>{{ $row['product'] }}</td>
                                        <td class="text-right">{{ number_format($row['quantity'], 3) }}</td>
                                        <td class="text-right">{{ number_format($row['amount'], 2) }}</td>
                                        <td><div style="background:#e5edf6;border-radius:999px;height:12px;"><div style="width:{{ $width }}%;height:12px;border-radius:999px;background:#06b6d4;"></div></div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="dd-empty">No product purchase data available.</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="dd-card">
                <div class="dd-card-header"><h3 class="dd-card-title">Order Status Summary</h3></div>
                <div class="dd-card-body dd-table-wrap">
                    <table class="dd-table" style="min-width:0;">
                        <thead><tr><th>Status</th><th class="text-right">Count</th></tr></thead>
                        <tbody>
                            @foreach($orderStatus as $row)
                                <tr><td>{{ $row['status'] }}</td><td class="text-right">{{ $row['count'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
