<div class="egg-kpi-grid">
    <a class="egg-kpi-card egg-kpi-blue" href="{{ route('egg.reports.production', request()->query()) }}">
        <div class="egg-kpi-icon"><i class="fa fa-circle"></i></div>
        <div class="egg-kpi-copy">
            <div class="egg-kpi-label">PRODUCTION</div>
            <div class="egg-kpi-value">{{ number_format($metrics['production_pieces']) }}</div>
            <div class="egg-kpi-caption">Pieces collected in range</div>
        </div>
        <div class="egg-kpi-open">Open <i class="fa fa-arrow-right"></i></div>
    </a>

    <a class="egg-kpi-card egg-kpi-green" href="{{ route('egg.grading.index') }}">
        <div class="egg-kpi-icon"><i class="fa fa-check-circle"></i></div>
        <div class="egg-kpi-copy">
            <div class="egg-kpi-label">GOOD EGGS</div>
            <div class="egg-kpi-value">{{ number_format($metrics['good_pieces']) }}</div>
            <div class="egg-kpi-caption">Good pieces in range</div>
        </div>
        <div class="egg-kpi-open">Open <i class="fa fa-arrow-right"></i></div>
    </a>

    <a class="egg-kpi-card egg-kpi-orange" href="{{ route('egg.stock.index') }}">
        <div class="egg-kpi-icon"><i class="fa fa-cubes"></i></div>
        <div class="egg-kpi-copy">
            <div class="egg-kpi-label">AVAILABLE STOCK</div>
            <div class="egg-kpi-value">{{ number_format($metrics['available_stock']) }}</div>
            <div class="egg-kpi-caption">Pieces available now</div>
        </div>
        <div class="egg-kpi-open">Open <i class="fa fa-arrow-right"></i></div>
    </a>

    <a class="egg-kpi-card egg-kpi-purple" href="{{ route('egg.reports.sales', request()->query()) }}">
        <div class="egg-kpi-icon"><i class="fa fa-shopping-cart"></i></div>
        <div class="egg-kpi-copy">
            <div class="egg-kpi-label">SALES</div>
            <div class="egg-kpi-value">{{ number_format($metrics['sales_total'], 4) }}</div>
            <div class="egg-kpi-caption">{{ number_format($metrics['sales_count']) }} transactions</div>
        </div>
        <div class="egg-kpi-open">Open <i class="fa fa-arrow-right"></i></div>
    </a>
</div>
