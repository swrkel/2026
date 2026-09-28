@extends('pos::layouts.app')
@section('title', 'POS Reports & Analytics')

@section('pos_content')
@php
    $fmt = function($value, $decimals = 4) { return number_format((float) $value, $decimals); };
    $from = $filters['from'] ?? request('from_date', now()->startOfMonth()->toDateString());
    $to = $filters['to'] ?? request('to_date', now()->toDateString());
@endphp

<div class="pos-hero report-hero">
    <div>
        <span class="eyebrow">Standalone POS</span>
        <h2>Reports & Analytics</h2>
        <p>Daily sales, item sales, category sales, payment, cashier, stock valuation and low stock reports.</p>
    </div>
    <div class="hero-actions">
        <a class="btn btn-primary btn-lg" href="{{ route('pos.sales.index') }}">Sales Workspace</a>
        <a class="btn btn-success btn-lg" href="{{ route('pos.products.index') }}">Products & Stock</a>
    </div>
</div>

<form method="get" class="box pos-card report-filter-card">
    <div class="box-body">
        <div class="row compact-row">
            <div class="col-md-3">
                <label>From Date</label>
                <input type="date" name="from_date" class="form-control pos-input-lg" value="{{ $from }}">
            </div>
            <div class="col-md-3">
                <label>To Date</label>
                <input type="date" name="to_date" class="form-control pos-input-lg" value="{{ $to }}">
            </div>
            <div class="col-md-6 text-right report-filter-actions">
                <button type="submit" class="btn btn-primary btn-lg">Search</button>
                <a href="{{ route('pos.reports.index') }}" class="btn btn-warning btn-lg">Reset</a>
                <button type="button" onclick="window.print()" class="btn btn-default btn-lg">Print</button>
            </div>
        </div>
    </div>
</form>

<div class="report-kpi-grid">
    <div class="report-kpi-card"><small>Total Sales</small><strong>{{ $fmt($summary['sales_total'] ?? 0) }}</strong><span>{{ $summary['sales_count'] ?? 0 }} bills</span></div>
    <div class="report-kpi-card green"><small>Net Sales</small><strong>{{ $fmt($summary['net_sales'] ?? 0) }}</strong><span>Sales less returns</span></div>
    <div class="report-kpi-card amber"><small>Payments</small><strong>{{ $fmt($summary['payments_total'] ?? 0) }}</strong><span>Collected amount</span></div>
    <div class="report-kpi-card red"><small>Returns</small><strong>{{ $fmt($summary['returns_total'] ?? 0) }}</strong><span>Refund / credit notes</span></div>
    <div class="report-kpi-card purple"><small>Gross Profit</small><strong>{{ $fmt($summary['gross_profit'] ?? 0) }}</strong><span>Estimated from item cost</span></div>
    <div class="report-kpi-card slate"><small>Stock Value</small><strong>{{ $fmt($summary['stock_value'] ?? 0) }}</strong><span>{{ $summary['low_stock_count'] ?? 0 }} low stock items</span></div>
</div>

<div class="box pos-card prominent-card">
    <div class="box-header smart-card-header">
        <div><strong>Daily Sales Report</strong><small>Bill count and total sales by day</small></div>
        <a class="btn btn-success" href="{{ route('pos.reports.export', ['report' => 'daily-sales', 'from_date' => $from, 'to_date' => $to]) }}">CSV</a>
    </div>
    <div class="box-body no-pad">
        <table class="table modern-table report-table">
            <thead><tr><th>Date</th><th class="text-right">Bills</th><th class="text-right">Total Amount</th></tr></thead>
            <tbody>
            @forelse($daily_sales as $row)
                <tr><td>{{ $row['report_date'] ?? '' }}</td><td class="text-right">{{ $row['bills'] ?? 0 }}</td><td class="text-right">{{ $fmt($row['total_amount'] ?? 0) }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center muted">No daily sales found for this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="box pos-card">
            <div class="box-header smart-card-header"><div><strong>Payment Report</strong><small>Grouped by payment method</small></div><a class="btn btn-success" href="{{ route('pos.reports.export', ['report' => 'payments', 'from_date' => $from, 'to_date' => $to]) }}">CSV</a></div>
            <div class="box-body no-pad">
                <table class="table modern-table report-table">
                    <thead><tr><th>Method</th><th class="text-right">Count</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                    @forelse($payment_summary as $row)
                        <tr><td>{{ ucfirst($row['payment_method'] ?? 'Unknown') }}</td><td class="text-right">{{ $row['payment_count'] ?? 0 }}</td><td class="text-right">{{ $fmt($row['amount'] ?? 0) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center muted">No payments found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box pos-card">
            <div class="box-header smart-card-header"><div><strong>Cashier Sales</strong><small>User-wise sales summary</small></div><a class="btn btn-success" href="{{ route('pos.reports.export', ['report' => 'cashier-sales', 'from_date' => $from, 'to_date' => $to]) }}">CSV</a></div>
            <div class="box-body no-pad">
                <table class="table modern-table report-table">
                    <thead><tr><th>Cashier ID</th><th class="text-right">Bills</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                    @forelse($cashier_sales as $row)
                        <tr><td>{{ ($row['cashier_id'] ?? 0) ?: 'Unknown' }}</td><td class="text-right">{{ $row['bills'] ?? 0 }}</td><td class="text-right">{{ $fmt($row['total_amount'] ?? 0) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center muted">No cashier sales found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="box pos-card">
    <div class="box-header smart-card-header"><div><strong>Item Sales Report</strong><small>Top 30 sold items by amount</small></div><a class="btn btn-success" href="{{ route('pos.reports.export', ['report' => 'item-sales', 'from_date' => $from, 'to_date' => $to]) }}">CSV</a></div>
    <div class="box-body no-pad">
        <table class="table modern-table report-table">
            <thead><tr><th>Product</th><th>SKU</th><th class="text-right">Quantity</th><th class="text-right">Total</th></tr></thead>
            <tbody>
            @forelse($item_sales as $row)
                <tr><td>{{ $row['product_name'] ?? '' }}</td><td>{{ $row['sku'] ?? '' }}</td><td class="text-right">{{ $fmt($row['quantity'] ?? 0, 3) }}</td><td class="text-right">{{ $fmt($row['total_amount'] ?? 0) }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center muted">No item sales found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="box pos-card">
            <div class="box-header smart-card-header"><div><strong>Category Sales</strong><small>Sales grouped by product category</small></div><a class="btn btn-success" href="{{ route('pos.reports.export', ['report' => 'category-sales', 'from_date' => $from, 'to_date' => $to]) }}">CSV</a></div>
            <div class="box-body no-pad">
                <table class="table modern-table report-table">
                    <thead><tr><th>Category</th><th class="text-right">Quantity</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                    @forelse($category_sales as $row)
                        <tr><td>{{ $row['category_name'] ?? 'Uncategorised' }}</td><td class="text-right">{{ $fmt($row['quantity'] ?? 0, 3) }}</td><td class="text-right">{{ $fmt($row['total_amount'] ?? 0) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center muted">No category sales found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box pos-card">
            <div class="box-header smart-card-header"><div><strong>Low Stock Report</strong><small>Items below alert quantity</small></div><a class="btn btn-success" href="{{ route('pos.reports.export', ['report' => 'low-stock']) }}">CSV</a></div>
            <div class="box-body no-pad">
                <table class="table modern-table report-table">
                    <thead><tr><th>Product</th><th>SKU</th><th class="text-right">Stock</th><th class="text-right">Alert</th></tr></thead>
                    <tbody>
                    @forelse($low_stock as $row)
                        <tr><td>{{ $row['name'] ?? '' }}</td><td>{{ $row['sku'] ?? '' }}</td><td class="text-right">{{ $fmt($row['stock_quantity'] ?? 0, 3) }}</td><td class="text-right">{{ $fmt($row['alert_quantity'] ?? 0, 3) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center muted">No low stock items found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="box pos-card">
    <div class="box-header smart-card-header"><div><strong>Stock Valuation</strong><small>Current stock value by product</small></div><a class="btn btn-success" href="{{ route('pos.reports.export', ['report' => 'stock-valuation']) }}">CSV</a></div>
    <div class="box-body no-pad">
        <table class="table modern-table report-table">
            <thead><tr><th>Product</th><th>SKU</th><th class="text-right">Stock</th><th class="text-right">Cost</th><th class="text-right">Value</th></tr></thead>
            <tbody>
            @forelse($stock_valuation as $row)
                <tr><td>{{ $row['name'] ?? '' }}</td><td>{{ $row['sku'] ?? '' }}</td><td class="text-right">{{ $fmt($row['stock_quantity'] ?? 0, 3) }}</td><td class="text-right">{{ $fmt($row['cost_price'] ?? 0) }}</td><td class="text-right">{{ $fmt($row['stock_value'] ?? 0) }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-center muted">No stock valuation data found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
