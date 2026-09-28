@extends('RiceMill::layout')

@section('rcm-title', 'Rice Mill Module')
@section('rcm-subtitle', 'Operational dashboard')

@php
    $rcmUser = auth()->user();
    $rcmSuperAdmin = false;
    if ($rcmUser) {
        foreach (['is_super_admin', 'super_admin'] as $field) {
            if (isset($rcmUser->{$field}) && (bool) $rcmUser->{$field}) {
                $rcmSuperAdmin = true;
                break;
            }
        }
        if (!$rcmSuperAdmin && method_exists($rcmUser, 'hasRole')) {
            try {
                $rcmSuperAdmin = $rcmUser->hasRole('Super Admin') || $rcmUser->hasRole('superadmin');
            } catch (\Throwable $e) {
                $rcmSuperAdmin = false;
            }
        }
    }

    $rcmCan = function ($permission) use ($rcmUser, $rcmSuperAdmin) {
        if ($rcmSuperAdmin) return true;
        if (!$rcmUser || !method_exists($rcmUser, 'can')) return false;
        try { return (bool) $rcmUser->can($permission); } catch (\Throwable $e) { return false; }
    };
@endphp

@section('rcm-actions')
    @if($rcmCan('rice_mill.reports.view'))
        <a class="rcm-btn rcm-btn-light" href="{{ route('rice-mill.reports.index') }}">
            <i class="fa fa-bar-chart"></i> Reports
        </a>
    @endif
@endsection

@section('rcm-content')
<div class="rcm-dashboard">
    <div class="rcm-welcome-card">
        <div class="rcm-welcome-copy">
            <span class="rcm-eyebrow">RICE MILL OPERATIONS</span>
            <h2>From paddy receiving to finished rice, in one place.</h2>
            <p>Use the shortcuts below for daily work and check the live cards for your current stock, production and sales position.</p>
        </div>
        <div class="rcm-welcome-badge" aria-hidden="true">
            <div class="rcm-grain-icon"><i class="fa fa-leaf"></i></div>
            <span>Production Control</span>
        </div>
    </div>

    <div class="rcm-quick-grid">
        @if($rcmCan('rice_mill.paddy_purchase.create'))
            <a class="rcm-quick-card rcm-quick-purchase" href="{{ route('rice-mill.purchases.create') }}">
                <span class="rcm-quick-icon"><i class="fa fa-shopping-basket"></i></span>
                <span class="rcm-quick-copy"><strong>Purchase Order</strong><small>Create a new Purchase Order</small></span>
                <i class="fa fa-chevron-right rcm-quick-arrow"></i>
            </a>
        @endif

        @if($rcmCan('rice_mill.paddy_receipt.create'))
            <a class="rcm-quick-card rcm-quick-receive" href="{{ route('rice-mill.receipts.create') }}">
                <span class="rcm-quick-icon"><i class="fa fa-truck"></i></span>
                <span class="rcm-quick-copy"><strong>Receive Paddy</strong><small>Weighbridge & receiving entry</small></span>
                <i class="fa fa-chevron-right rcm-quick-arrow"></i>
            </a>
        @endif

        @if($rcmCan('rice_mill.production.create') && $rcmCan('rice_mill.production.complete'))
            <a class="rcm-quick-card rcm-quick-production" href="{{ route('rice-mill.production.create') }}">
                <span class="rcm-quick-icon"><i class="fa fa-cogs"></i></span>
                <span class="rcm-quick-copy"><strong>Milling / Production</strong><small>Open production operation</small></span>
                <i class="fa fa-chevron-right rcm-quick-arrow"></i>
            </a>
        @endif

        @if($rcmCan('rice_mill.packing.create'))
            <a class="rcm-quick-card rcm-quick-packing" href="{{ route('rice-mill.packing.create') }}">
                <span class="rcm-quick-icon"><i class="fa fa-cube"></i></span>
                <span class="rcm-quick-copy"><strong>Packing</strong><small>Record a packing batch</small></span>
                <i class="fa fa-chevron-right rcm-quick-arrow"></i>
            </a>
        @endif

        @if($rcmCan('rice_mill.dispatch.create'))
            <a class="rcm-quick-card rcm-quick-dispatch" href="{{ route('rice-mill.dispatch.create') }}">
                <span class="rcm-quick-icon"><i class="fa fa-send"></i></span>
                <span class="rcm-quick-copy"><strong>Sales / Dispatch</strong><small>Create a new dispatch</small></span>
                <i class="fa fa-chevron-right rcm-quick-arrow"></i>
            </a>
        @endif

        @if($rcmCan('rice_mill.reports.view'))
            <a class="rcm-quick-card rcm-quick-report" href="{{ route('rice-mill.reports.index') }}">
                <span class="rcm-quick-icon"><i class="fa fa-line-chart"></i></span>
                <span class="rcm-quick-copy"><strong>Reports</strong><small>Stock, production & profitability</small></span>
                <i class="fa fa-chevron-right rcm-quick-arrow"></i>
            </a>
        @endif
    </div>

    <div class="rcm-kpi-grid">
        <div class="rcm-kpi-card rcm-kpi-paddy">
            <div class="rcm-kpi-icon"><i class="fa fa-leaf"></i></div>
            <div class="rcm-kpi-content">
                <div class="rcm-kpi-label">Paddy Stock</div>
                <div class="rcm-kpi-value">{{ number_format($data['paddy_stock'], $rcmQuantityPrecision) }} <span>kg</span></div>
                <div class="rcm-kpi-foot">Available paddy balance</div>
            </div>
        </div>

        <div class="rcm-kpi-card rcm-kpi-rice">
            <div class="rcm-kpi-icon"><i class="fa fa-database"></i></div>
            <div class="rcm-kpi-content">
                <div class="rcm-kpi-label">Finished Rice Stock</div>
                <div class="rcm-kpi-value">{{ number_format($data['rice_stock'], $rcmQuantityPrecision) }} <span>kg</span></div>
                <div class="rcm-kpi-foot">Ready finished stock</div>
            </div>
        </div>

        <div class="rcm-kpi-card rcm-kpi-production">
            <div class="rcm-kpi-icon"><i class="fa fa-industry"></i></div>
            <div class="rcm-kpi-content">
                <div class="rcm-kpi-label">Selected Period Production</div>
                <div class="rcm-kpi-value">{{ number_format($data['period_production'], $rcmQuantityPrecision) }} <span>kg</span></div>
                <div class="rcm-kpi-foot">Completed in selected date range</div>
            </div>
        </div>

        <div class="rcm-kpi-card rcm-kpi-sales">
            <div class="rcm-kpi-icon"><i class="fa fa-money"></i></div>
            <div class="rcm-kpi-content">
                <div class="rcm-kpi-label">Selected Period Sales</div>
                <div class="rcm-kpi-value">{{ number_format($data['period_sales'], $rcmCurrencyPrecision) }}</div>
                <div class="rcm-kpi-foot">Approved dispatch value in selected range</div>
            </div>
        </div>

        <div class="rcm-kpi-card rcm-kpi-yield">
            <div class="rcm-kpi-icon"><i class="fa fa-percent"></i></div>
            <div class="rcm-kpi-content">
                <div class="rcm-kpi-label">Average Rice Yield</div>
                <div class="rcm-kpi-value">{{ number_format($data['avg_yield'], 2) }}<span>%</span></div>
                <div class="rcm-kpi-foot">Completed production batches</div>
            </div>
        </div>
    </div>

    <div class="rcm-panel rcm-flow-panel">
        <div class="rcm-panel-head">
            <div>
                <span class="rcm-panel-kicker">DAILY PROCESS</span>
                <h3>Rice Mill Operational Flow</h3>
            </div>
            <span class="rcm-panel-hint">Follow the process from left to right</span>
        </div>
        <div class="rcm-flow">
            <div class="rcm-flow-step"><span>1</span><i class="fa fa-shopping-basket"></i><strong>Purchase Order</strong><small>Buy paddy</small></div>
            <div class="rcm-flow-line"></div>
            <div class="rcm-flow-step"><span>2</span><i class="fa fa-balance-scale"></i><strong>Receive</strong><small>Weight & quality</small></div>
            <div class="rcm-flow-line"></div>
            <div class="rcm-flow-step"><span>3</span><i class="fa fa-archive"></i><strong>Paddy Stock</strong><small>Lot balances</small></div>
            <div class="rcm-flow-line"></div>
            <div class="rcm-flow-step"><span>4</span><i class="fa fa-cogs"></i><strong>Milling</strong><small>Production batch</small></div>
            <div class="rcm-flow-line"></div>
            <div class="rcm-flow-step"><span>5</span><i class="fa fa-cube"></i><strong>Packing</strong><small>Finished rice</small></div>
            <div class="rcm-flow-line"></div>
            <div class="rcm-flow-step"><span>6</span><i class="fa fa-truck"></i><strong>Dispatch</strong><small>Customer delivery</small></div>
        </div>
    </div>

    <div class="rcm-dashboard-bottom">
        <div class="rcm-panel rcm-batches-panel">
            <div class="rcm-panel-head">
                <div>
                    <span class="rcm-panel-kicker">PRODUCTION</span>
                    <h3>Recent Milling Batches</h3>
                </div>
                @if($rcmCan('rice_mill.production.create') && $rcmCan('rice_mill.production.complete'))
                    <a class="rcm-text-link" href="{{ route('rice-mill.production.create') }}">Open operation <i class="fa fa-angle-right"></i></a>
                @elseif($rcmCan('rice_mill.production.view'))
                    <a class="rcm-text-link" href="{{ route('rice-mill.production.index') }}">View history <i class="fa fa-angle-right"></i></a>
                @endif
            </div>

            @include('RiceMill::partials.functionality-bar', ['tableId'=>'rcm-dashboard-batches-table','exportName'=>'rice-mill-dashboard-milling-batches','serverPaged'=>true,'paginator'=>$data['recent_batches'],'rowsLabel'=>'batches'])
            <div class="rcm-table-wrap">
                <table id="rcm-dashboard-batches-table" class="rcm-table rcm-dashboard-table rcm-managed-table">
                    <thead>
                        <tr>
                            <th>Batch</th>
                            <th>Status</th>
                            <th class="rcm-num">Paddy Input</th>
                            <th class="rcm-num">Rice Output</th>
                            <th class="rcm-num">Yield %</th>
                            <th class="rcm-num">Cost / Kg</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($data['recent_batches'] as $r)
                        <tr>
                            <td><strong>{{ $r->batch_no }}</strong></td>
                            <td><span class="rcm-status rcm-status-{{ strtolower(str_replace(' ', '-', $r->status)) }}">{{ ucwords(str_replace('_', ' ', $r->status)) }}</span></td>
                            <td class="rcm-num">{{ number_format($r->input_qty, $rcmQuantityPrecision) }}</td>
                            <td class="rcm-num">{{ number_format($r->rice_output_qty, $rcmQuantityPrecision) }}</td>
                            <td class="rcm-num">{{ number_format($r->rice_yield_percent, 2) }}</td>
                            <td class="rcm-num">{{ number_format($r->cost_per_kg, $rcmCurrencyPrecision) }}</td>
                        </tr>
                    @empty
                        <tr data-rcm-empty-row>
                            <td colspan="6">
                                <div class="rcm-empty-state">
                                    <i class="fa fa-industry"></i>
                                    <strong>No production batches yet</strong>
                                    <span>Create the first milling batch when production starts.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $data['recent_batches']->links() }}
        </div>

        <div class="rcm-panel rcm-stock-links-panel">
            <div class="rcm-panel-head">
                <div>
                    <span class="rcm-panel-kicker">STOCK CONTROL</span>
                    <h3>Stock & Operations</h3>
                </div>
            </div>
            <div class="rcm-side-links">
                @if($rcmCan('rice_mill.paddy_stock.view'))
                    <a href="{{ route('rice-mill.paddy-stock.index') }}"><span class="rcm-side-icon"><i class="fa fa-leaf"></i></span><span><strong>Paddy Stock</strong><small>View lot balances & ledger</small></span><i class="fa fa-angle-right"></i></a>
                @endif
                @if($rcmCan('rice_mill.finished_stock.view'))
                    <a href="{{ route('rice-mill.finished-stock.index') }}"><span class="rcm-side-icon"><i class="fa fa-cubes"></i></span><span><strong>Finished Rice Stock</strong><small>View rice product balances</small></span><i class="fa fa-angle-right"></i></a>
                @endif
                @if($rcmCan('rice_mill.production.view'))
                    <a href="{{ route('rice-mill.byproducts.index') }}"><span class="rcm-side-icon"><i class="fa fa-recycle"></i></span><span><strong>By-Products</strong><small>Broken rice, bran & husk</small></span><i class="fa fa-angle-right"></i></a>
                @endif
                @if($rcmCan('rice_mill.paddy_receipt.view'))
                    <a href="{{ route('rice-mill.weighbridge.index') }}"><span class="rcm-side-icon"><i class="fa fa-balance-scale"></i></span><span><strong>Weighbridge</strong><small>Review received weights</small></span><i class="fa fa-angle-right"></i></a>
                @endif
                @if($rcmCan('rice_mill.settings.view'))
                    <a href="{{ route('rice-mill.settings.index') }}"><span class="rcm-side-icon"><i class="fa fa-cog"></i></span><span><strong>Rice Mill Settings</strong><small>Varieties, mills & products</small></span><i class="fa fa-angle-right"></i></a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
