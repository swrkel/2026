@extends('pos::layouts.app', ['title' => $title ?? 'POS Returns'])

@section('pos_page_description', 'Process returns, refunds and exchanges with the same POS dashboard design and controls.')
@section('pos_page_actions')
    <a href="{{ url('/pos-module') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
    <a href="{{ url('/pos-module/returns/create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> New Return</a>
    <a href="{{ url('/pos-module/exchanges') }}" class="btn btn-success btn-sm"><i class="fa fa-exchange"></i> Exchanges</a>
@endsection

@section('pos_content')
@if(!empty($warnings))
    <div class="alert alert-warning"><i class="fa fa-warning"></i> {{ implode(' ', array_unique($warnings)) }} The page is still available.</div>
@endif

@php
    $cp = (int) session('business.currency_precision', 2);
    $cards = [
        ['label' => 'Total Returns', 'value' => number_format((float) data_get($stats, 'returns_count', 0)), 'icon' => 'fa-undo', 'hint' => 'Selected period', 'tone' => '', 'url' => url('/pos-module/returns')],
        ['label' => 'Refund Total', 'value' => number_format((float) data_get($stats, 'refund_total', 0), $cp), 'icon' => 'fa-money', 'hint' => 'All refund methods', 'tone' => 'success'],
        ['label' => 'Pending Approval', 'value' => number_format((float) data_get($stats, 'pending_approval', 0)), 'icon' => 'fa-check-square-o', 'hint' => 'Manager review', 'tone' => 'warning'],
        ['label' => 'Exchanges', 'value' => number_format((float) data_get($stats, 'exchanges_count', 0)), 'icon' => 'fa-exchange', 'hint' => 'Product exchanges', 'tone' => 'purple', 'url' => url('/pos-module/exchanges')],
    ];
@endphp
<div class="ch-kpi-grid ch-standard-grid">
    @foreach($cards as $card)
        @include('pos::pagefix.partials.kpi-card', ['card' => $card])
    @endforeach
</div>

<div class="ch-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-undo text-primary"></i> Returns & Refunds</h3>
            <div class="ch-card-subtitle">Search, review and print POS return transactions.</div>
        </div>
        <div class="ch-quick-actions">
            <a class="btn btn-success btn-sm" href="{{ url('/pos-module/returns/create') }}"><i class="fa fa-plus"></i> New Return</a>
            <a class="btn btn-primary btn-sm" href="{{ url('/pos-module/exchanges/create') }}"><i class="fa fa-exchange"></i> New Exchange</a>
        </div>
    </div>
    <div class="ch-card-body">
        <form method="get" action="{{ url('/pos-module/returns') }}" class="ch-toolbar">
            <div style="position:relative;min-width:260px;max-width:440px;flex:1;">
                <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search return, sale, invoice or customer" style="padding-right:38px;">
                <i class="fa fa-search" style="position:absolute;right:14px;top:13px;color:#64748b;"></i>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <input class="form-control" type="date" name="start_date" value="{{ request('start_date') }}" aria-label="Start date">
                <input class="form-control" type="date" name="end_date" value="{{ request('end_date') }}" aria-label="End date">
                <button class="btn btn-primary" type="submit"><i class="fa fa-filter"></i> Apply</button>
                <a class="btn btn-default" href="{{ url('/pos-module/returns') }}"><i class="fa fa-refresh"></i> Reset</a>
                <button class="btn btn-default" type="button" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered pos-standard-table">
                <thead><tr><th>Return No</th><th>Sale No</th><th>Date</th><th>Customer</th><th>Refund</th><th class="text-right">Amount</th><th>Status</th><th>Approval</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($returns as $returnRow)
                    @php($returnId = data_get($returnRow, 'id'))
                    <tr>
                        <td><strong>{{ data_get($returnRow, 'return_no', 'POS Return') }}</strong></td>
                        <td>{{ data_get($returnRow, 'sale_no', data_get($returnRow, 'invoice_no', '-')) }}</td>
                        <td>{{ data_get($returnRow, 'return_date', data_get($returnRow, 'created_at', '-')) }}</td>
                        <td>{{ data_get($returnRow, 'customer_name') ?: 'Walk-in' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', (string) data_get($returnRow, 'refund_method', 'cash'))) }}</td>
                        <td class="text-right">{{ number_format((float) data_get($returnRow, 'total_amount', 0), $cp) }}</td>
                        <td><span class="label label-{{ data_get($returnRow, 'status', 'final') === 'final' ? 'success' : 'warning' }}">{{ ucfirst((string) data_get($returnRow, 'status', 'final')) }}</span></td>
                        <td><span class="label label-{{ data_get($returnRow, 'approval_status', 'approved') === 'approved' ? 'success' : 'warning' }}">{{ ucfirst((string) data_get($returnRow, 'approval_status', 'approved')) }}</span></td>
                        <td>
                            @if($returnId)
                                <a class="btn btn-xs btn-default" href="{{ url('/pos-module/returns/' . $returnId . '/receipt') }}"><i class="fa fa-print"></i> Receipt</a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9"><div class="empty-state"><i class="fa fa-undo fa-2x"></i><h4>No returns found</h4><p>Use New Return to process the first return.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if(is_object($returns) && method_exists($returns, 'links'))
            <div style="margin-top:16px;">{{ $returns->links() }}</div>
        @endif
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header">
        <div><h3 class="ch-card-title"><i class="fa fa-shopping-cart text-primary"></i> Recent Sales</h3><div class="ch-card-subtitle">Recent transactions available for return processing.</div></div>
    </div>
    <div class="ch-card-body table-responsive">
        <table class="table table-bordered pos-standard-table">
            <thead><tr><th>Sale No</th><th>Date</th><th>Customer</th><th class="text-right">Total</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($sales as $sale)
                <tr>
                    <td>{{ data_get($sale, 'sale_no', data_get($sale, 'invoice_no', data_get($sale, 'id') ? 'POS-' . data_get($sale, 'id') : '-')) }}</td>
                    <td>{{ data_get($sale, 'sale_date', data_get($sale, 'created_at', '-')) }}</td>
                    <td>{{ data_get($sale, 'customer_name') ?: 'Walk-in' }}</td>
                    <td class="text-right">{{ number_format((float) data_get($sale, 'total_amount', 0), $cp) }}</td>
                    <td>{{ ucfirst((string) data_get($sale, 'status', 'final')) }}</td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty-state"><i class="fa fa-shopping-cart fa-2x"></i><h4>No recent sales found</h4><p>Completed POS sales will be listed here.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
