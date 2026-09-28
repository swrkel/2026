@extends('stocktransfernew::layouts.app')

@section('stocktransfernew_content')
@include('stocktransfernew::partials.header', [
    'title' => 'Stock Transfer Dashboard',
    'subtitle' => 'Monitor and manage transfers across businesses, locations and stores'
])

@php
    $cards = [
        ['key' => 'total_transfers', 'label' => 'Total Transfers', 'icon' => 'fa-exchange', 'tone' => 'blue'],
        ['key' => 'draft', 'label' => 'Draft', 'icon' => 'fa-file-text-o', 'tone' => 'slate'],
        ['key' => 'pending_approval', 'label' => 'Pending Approval', 'icon' => 'fa-clock-o', 'tone' => 'amber'],
        ['key' => 'approved', 'label' => 'Approved', 'icon' => 'fa-check-circle', 'tone' => 'green'],
        ['key' => 'ready_for_dispatch', 'label' => 'Ready for Dispatch', 'icon' => 'fa-cubes', 'tone' => 'purple'],
        ['key' => 'in_transit', 'label' => 'In Transit', 'icon' => 'fa-truck', 'tone' => 'cyan'],
        ['key' => 'partially_received', 'label' => 'Partially Received', 'icon' => 'fa-adjust', 'tone' => 'orange'],
        ['key' => 'completed', 'label' => 'Completed', 'icon' => 'fa-flag-checkered', 'tone' => 'emerald'],
        ['key' => 'rejected_returned', 'label' => 'Rejected / Returned', 'icon' => 'fa-reply', 'tone' => 'red'],
        ['key' => 'cancelled', 'label' => 'Cancelled', 'icon' => 'fa-ban', 'tone' => 'gray'],
    ];
@endphp

<section class="stn-kpi-grid">
    @foreach($cards as $card)
        <article class="stn-kpi-card stn-tone-{{ $card['tone'] }}">
            <div class="stn-kpi-icon">
                <i class="fa {{ $card['icon'] }}"></i>
            </div>
            <div class="stn-kpi-content">
                <span>{{ $card['label'] }}</span>
                <strong>{{ number_format((int) ($stats[$card['key']] ?? 0)) }}</strong>
            </div>
        </article>
    @endforeach
</section>

<section class="stn-dashboard-grid">
    <article class="stn-panel stn-command-panel">
        <div class="stn-panel-heading">
            <div>
                <h2>Command Centre</h2>
                <p>Quick access to daily transfer operations</p>
            </div>
            <i class="fa fa-th-large"></i>
        </div>

        <div class="stn-action-grid">
            @if(\Illuminate\Support\Facades\Route::has('stock-transfer-new.transfers.create'))
                <a href="{{ route('stock-transfer-new.transfers.create') }}" class="stn-action-card">
                    <span class="stn-action-icon stn-action-blue"><i class="fa fa-plus"></i></span>
                    <strong>Create Transfer</strong>
                    <small>Start a new stock transfer request</small>
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('stock-transfer-new.transfers.index'))
                <a href="{{ route('stock-transfer-new.transfers.index') }}" class="stn-action-card">
                    <span class="stn-action-icon stn-action-purple"><i class="fa fa-list"></i></span>
                    <strong>Transfer Register</strong>
                    <small>Review all stock transfer records</small>
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('stock-transfer-new.approvals.index'))
                <a href="{{ route('stock-transfer-new.approvals.index') }}" class="stn-action-card">
                    <span class="stn-action-icon stn-action-amber"><i class="fa fa-check-square-o"></i></span>
                    <strong>Pending Approvals</strong>
                    <small>Approve or return submitted requests</small>
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('stock-transfer-new.dispatch.index'))
                <a href="{{ route('stock-transfer-new.dispatch.index') }}" class="stn-action-card">
                    <span class="stn-action-icon stn-action-cyan"><i class="fa fa-truck"></i></span>
                    <strong>Dispatch Queue</strong>
                    <small>Process approved outbound transfers</small>
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('stock-transfer-new.receive.index'))
                <a href="{{ route('stock-transfer-new.receive.index') }}" class="stn-action-card">
                    <span class="stn-action-icon stn-action-green"><i class="fa fa-download"></i></span>
                    <strong>Receive Stock</strong>
                    <small>Confirm incoming quantities and variances</small>
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('stock-transfer-new.reports.transfer-register'))
                <a href="{{ route('stock-transfer-new.reports.transfer-register') }}" class="stn-action-card">
                    <span class="stn-action-icon stn-action-red"><i class="fa fa-bar-chart"></i></span>
                    <strong>Reports</strong>
                    <small>Analyse transfer activity and performance</small>
                </a>
            @endif
        </div>
    </article>

    <aside class="stn-panel stn-workflow-panel">
        <div class="stn-panel-heading">
            <div>
                <h2>Transfer Workflow</h2>
                <p>Standard operational sequence</p>
            </div>
            <i class="fa fa-sitemap"></i>
        </div>

        <div class="stn-workflow">
            <div class="stn-workflow-step">
                <span>1</span>
                <div><strong>Draft</strong><small>Create and verify transfer items</small></div>
            </div>
            <div class="stn-workflow-line"></div>
            <div class="stn-workflow-step">
                <span>2</span>
                <div><strong>Approval</strong><small>Complete authorization controls</small></div>
            </div>
            <div class="stn-workflow-line"></div>
            <div class="stn-workflow-step">
                <span>3</span>
                <div><strong>Dispatch</strong><small>Release stock from source store</small></div>
            </div>
            <div class="stn-workflow-line"></div>
            <div class="stn-workflow-step">
                <span>4</span>
                <div><strong>Receive</strong><small>Confirm stock at destination store</small></div>
            </div>
        </div>
    </aside>
</section>
@endsection
