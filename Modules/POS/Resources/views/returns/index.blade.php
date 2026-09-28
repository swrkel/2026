@extends('layouts.app')
@section('title', $title ?? 'POS Returns')

@section('content')
@include('pos::partials.erp-standard-styles')
<section class="content pos-erp-standard-ui communication-hub-ui">
<div class="ch-shell">
    <div class="ch-hero pos-module-hero">
        <div>
            <div class="ch-eyebrow">POS Module</div>
            <h1>{{ $title ?? 'POS Returns' }}</h1>
            <p>Process returns, refunds, exchanges and sale voids from one page.</p>
        </div>
        <div class="ch-quick-actions">
            <a href="{{ url('/pos-module') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
            <a href="{{ url('/pos-module/sales') }}" class="btn btn-primary btn-sm"><i class="fa fa-shopping-cart"></i> Sales</a>
        </div>
    </div>

    @if(!empty($pageWarning))
        <div class="alert alert-warning"><i class="fa fa-warning"></i> {{ $pageWarning }}</div>
    @endif
<div class="row ch-kpi-row">
    <div class="col-md-3 col-sm-6"><div class="ch-kpi ch-kpi-blue"><div class="ch-kpi-icon"><i class="fa fa-undo"></i></div><div><small>Total Returns</small><h2>{{ number_format((float)($stats['returns_count'] ?? 0)) }}</h2><span>This period</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="ch-kpi ch-kpi-green"><div class="ch-kpi-icon"><i class="fa fa-money"></i></div><div><small>Refund Total</small><h2>{{ number_format((float)($stats['refund_total'] ?? 0), 4) }}</h2><span>Cash/card/credit</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="ch-kpi ch-kpi-orange"><div class="ch-kpi-icon"><i class="fa fa-check-square-o"></i></div><div><small>Pending Approval</small><h2>{{ number_format((float)($stats['pending_approval'] ?? 0)) }}</h2><span>Manager review</span></div></div></div>
    <div class="col-md-3 col-sm-6"><div class="ch-kpi ch-kpi-purple"><div class="ch-kpi-icon"><i class="fa fa-exchange"></i></div><div><small>Exchanges</small><h2>{{ number_format((float)($stats['exchanges_count'] ?? 0)) }}</h2><span>Product exchange</span></div></div></div>
</div>

<div class="box box-solid ch-card">
    <div class="box-header with-border ch-card-header">
        <h3 class="box-title"><i class="fa fa-undo"></i> Returns & Refunds</h3>
        <div class="box-tools pull-right">
            <a class="btn btn-success btn-sm" href="{{ route('pos.returns.create') }}"><i class="fa fa-plus"></i> New Return</a>
            <a class="btn btn-primary btn-sm" href="{{ route('pos.exchanges.create') }}"><i class="fa fa-exchange"></i> New Exchange</a>
            <a class="btn btn-default btn-sm" href="{{ route('pos.exchanges.index') }}"><i class="fa fa-list"></i> Exchanges</a>
            <a class="btn btn-info btn-sm" href="{{ route('pos.returns.export_csv', request()->query()) }}"><i class="fa fa-file-excel-o"></i> CSV</a>
        </div>
    </div>
    <div class="box-body">
        <form method="get" class="row ch-filter-row">
            <div class="col-md-3"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search return / sale / invoice"></div>
            <div class="col-md-2"><input class="form-control" type="date" name="start_date" value="{{ request('start_date') }}"></div>
            <div class="col-md-2"><input class="form-control" type="date" name="end_date" value="{{ request('end_date') }}"></div>
            <div class="col-md-3"><button class="btn btn-primary"><i class="fa fa-search"></i> Search</button> <a class="btn btn-default" href="{{ route('pos.returns.index') }}">Reset</a></div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover table-striped ch-table">
                <thead><tr><th>Return No</th><th>Sale No</th><th>Date</th><th>Customer</th><th>Refund</th><th class="text-right">Amount</th><th>Status</th><th>Approval</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($returns as $return)
                    @php
                        $returnId = data_get($return, 'id');
                        $returnNo = data_get($return, 'return_no', 'RET-' . ($returnId ?: ''));
                        $saleNo = data_get($return, 'sale_no') ?: data_get($return, 'invoice_no', '-');
                        $returnDate = data_get($return, 'return_date', '-');
                        $customerName = data_get($return, 'customer_name') ?: 'Walk-in';
                        $refundMethod = data_get($return, 'refund_method', 'cash');
                        $returnAmount = (float) data_get($return, 'total_amount', 0);
                        $returnStatus = data_get($return, 'status', 'final');
                        $approvalStatus = data_get($return, 'approval_status', 'approved');
                    @endphp
                    <tr>
                        <td><strong>{{ $returnNo }}</strong></td>
                        <td>{{ $saleNo }}</td>
                        <td>{{ $returnDate }}</td>
                        <td>{{ $customerName }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $refundMethod)) }}</td>
                        <td class="text-right">{{ number_format($returnAmount, 4) }}</td>
                        <td><span class="label label-{{ $returnStatus === 'final' ? 'success' : 'warning' }}">{{ ucfirst($returnStatus) }}</span></td>
                        <td><span class="label label-{{ $approvalStatus === 'approved' ? 'success' : ($approvalStatus === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($approvalStatus) }}</span></td>
                        <td class="text-nowrap">
                            @if($returnId)
                                <a class="btn btn-xs btn-default" href="{{ route('pos.returns.receipt', $returnId) }}"><i class="fa fa-print"></i> Receipt</a>
                                @if($approvalStatus === 'pending')
                                    <form method="post" action="{{ route('pos.returns.approve', $returnId) }}" style="display:inline">@csrf<input type="hidden" name="approval_status" value="approved"><button class="btn btn-xs btn-success" onclick="return confirm('Approve this return?')"><i class="fa fa-check"></i></button></form>
                                    <form method="post" action="{{ route('pos.returns.approve', $returnId) }}" style="display:inline">@csrf<input type="hidden" name="approval_status" value="rejected"><button class="btn btn-xs btn-danger" onclick="return confirm('Reject this return?')"><i class="fa fa-times"></i></button></form>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">No returns found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(is_object($returns) && method_exists($returns, 'links')) {{ $returns->links() }} @endif
    </div>
</div>

<div class="box box-solid ch-card">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-ban"></i> Recent Sales - Quick Void</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-hover ch-table">
            <thead><tr><th>Sale No</th><th>Date</th><th>Customer</th><th class="text-right">Total</th><th>Status</th><th>Void</th></tr></thead>
            <tbody>
            @forelse($sales as $sale)
                @php
                    $saleId = data_get($sale, 'id');
                    $saleNo = data_get($sale, 'sale_no') ?: data_get($sale, 'invoice_no') ?: ('POS-' . ($saleId ?: ''));
                    $saleDate = data_get($sale, 'sale_date') ?: data_get($sale, 'created_at', '-');
                    $saleCustomer = data_get($sale, 'customer_name') ?: 'Walk-in';
                    $saleTotal = (float) data_get($sale, 'total_amount', 0);
                    $saleStatus = data_get($sale, 'status', 'final');
                @endphp
                <tr>
                    <td>{{ $saleNo }}</td>
                    <td>{{ $saleDate }}</td>
                    <td>{{ $saleCustomer }}</td>
                    <td class="text-right">{{ number_format($saleTotal, 4) }}</td>
                    <td>{{ ucfirst($saleStatus) }}</td>
                    <td>
                        @if($saleId)
                            <form method="post" action="{{ route('pos.sales.void', $saleId) }}" onsubmit="return confirm('Void this bill and restore stock?');">@csrf<input type="hidden" name="reason" value="Voided from POS returns page"><button class="btn btn-xs btn-danger" type="submit"><i class="fa fa-ban"></i> Void</button></form>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No recent sales found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
</section>
@endsection
