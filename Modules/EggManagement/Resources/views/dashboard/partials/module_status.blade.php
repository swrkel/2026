<div class="egg-pos-panel egg-status-panel">
    <div class="egg-pos-panel-head">
        <div class="egg-panel-title-wrap">
            <span class="egg-panel-title-icon egg-panel-title-icon-green"><i class="fa fa-shield"></i></span>
            <div>
                <h3>Module Status</h3>
                <p>Current Egg Management operational summary.</p>
            </div>
        </div>
        <span class="egg-active-badge"><i class="fa fa-check-circle"></i> Active</span>
    </div>
    <div class="egg-status-grid">
        <div class="egg-status-stat"><span>Active Stock Lots</span><strong>{{ number_format($metrics['active_stock_lots'] ?? 0) }}</strong></div>
        <div class="egg-status-stat"><span>Pending Integrations</span><strong>{{ number_format($metrics['pending_integrations'] ?? 0) }}</strong></div>
    </div>
    <a href="{{ route('egg.reports.production', request()->query()) }}" class="egg-status-action"><i class="fa fa-chart-bar"></i><span>Open Egg Management Reports</span><i class="fa fa-chevron-right"></i></a>
</div>
