@extends('pos::layouts.app', ['title' => __('pos::messages.dashboard')])

@section('pos_content')
@php
    $cards = [
        ['label' => __('pos::messages.sales_today'), 'value' => $summary['sales_today'] ?? '0.00', 'icon' => 'fa-comments', 'sub' => 'Total sales', 'tone' => '', 'url' => url('/pos-module/sales')],
        ['label' => __('pos::messages.payments_today'), 'value' => $summary['payments_today'] ?? '0.00', 'icon' => 'fa-credit-card', 'sub' => 'Total payments', 'tone' => 'success', 'url' => url('/pos-module/reports')],
        ['label' => __('pos::messages.open_registers'), 'value' => $summary['open_registers'] ?? '0', 'icon' => 'fa-archive', 'sub' => 'Active registers', 'tone' => 'warning', 'url' => url('/pos-module/registers')],
        ['label' => __('pos::messages.cash_drawer'), 'value' => $summary['cash_drawer'] ?? '0.00', 'icon' => 'fa-money', 'sub' => 'Drawer balance', 'tone' => 'purple', 'url' => url('/pos-module/cash-drawer')],
    ];
@endphp

<div class="ch-kpi-grid ch-standard-grid">
@foreach($cards as $card)
    <a href="{{ $card['url'] }}" class="ch-kpi-link">
        <div class="ch-kpi {{ $card['tone'] }}">
            <div class="ch-kpi-top">
                <div class="ch-icon"><i class="fa {{ $card['icon'] }}"></i></div>
                <div class="label-text">{{ $card['label'] }}</div>
            </div>
            <div class="value">{{ $card['value'] }}</div>
            <div class="hint">{{ $card['sub'] }} <span class="ch-drill">Open <i class="fa fa-angle-right"></i></span></div>
            <div class="spark"></div>
        </div>
    </a>
@endforeach
</div>

<div class="pos-dashboard-panels">
    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-list-alt text-primary"></i> {{ __('pos::messages.recent_transactions') }}</h3>
                <div class="ch-card-subtitle">Latest POS sales and payment activity.</div>
            </div>
        </div>
        <div class="ch-card-body">
            <div class="ch-toolbar">
                <div style="position:relative;min-width:280px;max-width:420px;flex:1;">
                    <input type="text" class="form-control pos-instant-filter" placeholder="Search..." style="padding-right:38px;">
                    <i class="fa fa-search" style="position:absolute;right:14px;top:13px;color:#64748b;"></i>
                </div>
                <div class="pull-right">
                    <button class="btn btn-default"><i class="fa fa-eye"></i> {{ __('pos::messages.column_visibility') }}</button>
                    <button class="btn btn-default"><i class="fa fa-print"></i> {{ __('pos::messages.print') }}</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered pos-standard-table">
                    <thead>
                        <tr>
                            <th>{{ __('pos::messages.date') }}</th>
                            <th>{{ __('pos::messages.invoice_no') }}</th>
                            <th>{{ __('pos::messages.customer') }}</th>
                            <th class="text-right">{{ __('pos::messages.amount') }}</th>
                            <th>{{ __('pos::messages.status') }}</th>
                        </tr>
                    </thead>
                    <tbody><tr><td colspan="5" class="text-muted">{{ __('pos::messages.no_records_yet') }}</td></tr></tbody>
                </table>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;color:#52627a;">
                <span>Showing 0 to 0 of 0 entries</span>
                <span><button class="btn btn-default" style="width:38px;height:38px;padding:0;">‹</button> <button class="btn btn-default" style="width:38px;height:38px;padding:0;">›</button></span>
            </div>
        </div>
    </div>

    <div class="ch-card">
        <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-bolt text-primary"></i> {{ __('pos::messages.quick_actions') }}</h3><div class="ch-card-subtitle">Common POS administration actions.</div></div></div>
        <div class="ch-card-body pos-actions-list">
            <a href="{{ route('pos.configuration.index') }}" class="pos-action-tile"><span class="left"><span class="tile-icon"><i class="fa fa-cog"></i></span>{{ __('pos::messages.configuration_center') }}</span><i class="fa fa-angle-right"></i></a>
            <a href="{{ route('pos.status') }}" class="pos-action-tile success"><span class="left"><span class="tile-icon"><i class="fa fa-server"></i></span>{{ __('pos::messages.module_status') }}</span><i class="fa fa-angle-right"></i></a>
            <a href="{{ url('/pos-module/reports') }}" class="pos-action-tile warning"><span class="left"><span class="tile-icon"><i class="fa fa-file-text-o"></i></span>{{ __('pos::messages.system_notes') }}</span><i class="fa fa-angle-right"></i></a>
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-bolt text-warning"></i> Quick Operations</h3><div class="ch-card-subtitle">Start daily POS work from here.</div></div></div>
    <div class="ch-card-body pos-quick-operations">
        <a href="{{ url('/pos-module/sales/workspace') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-shopping-cart"></i></span><span><strong>New Sale</strong><span>Create new sale</span></span></a>
        <a href="{{ url('/pos-module/returns') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-undo"></i></span><span><strong>New Return</strong><span>Process return</span></span></a>
        <a href="{{ url('/pos-module/customers') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-user-plus"></i></span><span><strong>New Customer</strong><span>Add customer</span></span></a>
        <a href="{{ url('/pos-module/products/create') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-cube"></i></span><span><strong>Add Product</strong><span>Add new product</span></span></a>
        <a href="{{ url('/pos-module/registers') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-desktop"></i></span><span><strong>Open Register</strong><span>Open new register</span></span></a>
        <a href="{{ url('/pos-module/reports') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-bar-chart"></i></span><span><strong>Reports</strong><span>View reports</span></span></a>
    </div>
</div>
@endsection
