<div data-rcm-loaded-tab="{{ $activeTab ?? 'profitability' }}">
    <div class="rcm-card">
        <div class="rcm-panel-head"><h3>Rice Mill Profitability</h3><span class="rcm-panel-hint">Selected-period indicator</span></div>
        @include('RiceMill::partials.functionality-bar', ['tableId'=>'rcm-profitability-table','exportName'=>'rice-mill-profitability','serverPaged'=>false,'rowsLabel'=>'summary rows','dateEnabled'=>true])
        <form class="rcm-toolbar rcm-report-filter rcm-report-location-filter" method="get" action="{{ route('rice-mill.reports.index') }}">
            <input type="hidden" name="tab" value="profitability">
            <input type="hidden" name="range" value="{{ request('range','this_year') }}">
        <input type="hidden" name="q" value="{{ request('q') }}">
        <input type="hidden" name="per_page" value="{{ request('per_page','25') }}">
            @if(request('range')==='custom')<input type="hidden" name="from" value="{{ request('from') }}"><input type="hidden" name="to" value="{{ request('to') }}">@endif
            @include('RiceMill::reports.partials.location-store-filter')
            <button class="rcm-btn" type="submit"><i class="fa fa-filter"></i> Apply Location / Store</button>
        </form>
    </div>

    <div class="rcm-kpi-grid rcm-profitability-kpis">
        <div class="rcm-kpi-card rcm-kpi-rice">
            <div class="rcm-kpi-icon"><i class="fa fa-cubes" aria-hidden="true"></i></div>
            <div class="rcm-kpi-content">
                <div class="rcm-kpi-label">Rice Produced</div>
                <div class="rcm-kpi-value">{{ number_format($data['rice_produced'],$rcmQuantityPrecision) }} <span>kg</span></div>
                <div class="rcm-kpi-foot">Selected period production output</div>
            </div>
        </div>

        <div class="rcm-kpi-card rcm-kpi-production">
            <div class="rcm-kpi-icon"><i class="fa fa-industry" aria-hidden="true"></i></div>
            <div class="rcm-kpi-content">
                <div class="rcm-kpi-label">Production Cost</div>
                <div class="rcm-kpi-value">{{ number_format($data['production_cost'],$rcmCurrencyPrecision) }}</div>
                <div class="rcm-kpi-foot">Selected period milling / production cost</div>
            </div>
        </div>

        <div class="rcm-kpi-card rcm-kpi-sales">
            <div class="rcm-kpi-icon"><i class="fa fa-line-chart" aria-hidden="true"></i></div>
            <div class="rcm-kpi-content">
                <div class="rcm-kpi-label">Approved Sales</div>
                <div class="rcm-kpi-value">{{ number_format($data['sales'],$rcmCurrencyPrecision) }}</div>
                <div class="rcm-kpi-foot">Approved sales value for the selected period</div>
            </div>
        </div>

        <div class="rcm-kpi-card rcm-kpi-yield">
            <div class="rcm-kpi-icon"><i class="fa fa-pie-chart" aria-hidden="true"></i></div>
            <div class="rcm-kpi-content">
                <div class="rcm-kpi-label">Gross Margin Indicator</div>
                <div class="rcm-kpi-value">{{ number_format($data['gross_margin'],$rcmCurrencyPrecision) }}</div>
                <div class="rcm-kpi-foot">Approved sales less selected-period production cost</div>
            </div>
        </div>
    </div>

    <div class="rcm-card">
        <div class="rcm-table-wrap"><table id="rcm-profitability-table" class="rcm-table rcm-managed-table"><thead><tr><th>Period From</th><th>Period To</th><th class="rcm-num">Rice Produced</th><th class="rcm-num">Production Cost</th><th class="rcm-num">Approved Sales</th><th class="rcm-num">Gross Margin Indicator</th></tr></thead><tbody><tr><td>{{ $f }}</td><td>{{ $t }}</td><td class="rcm-num">{{ number_format($data['rice_produced'],$rcmQuantityPrecision) }}</td><td class="rcm-num">{{ number_format($data['production_cost'],$rcmCurrencyPrecision) }}</td><td class="rcm-num">{{ number_format($data['sales'],$rcmCurrencyPrecision) }}</td><td class="rcm-num">{{ number_format($data['gross_margin'],$rcmCurrencyPrecision) }}</td></tr></tbody></table></div>
    </div>
    <div class="rcm-card rcm-muted">Gross margin here compares selected-period production cost and selected-period approved dispatch value. For audited accounting profit, use the Finance Module after Finance outbox entries are processed.</div>
</div>
