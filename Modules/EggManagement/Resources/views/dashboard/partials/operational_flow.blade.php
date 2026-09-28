<div class="egg-pos-panel egg-flow-panel">
    <div class="egg-pos-panel-head">
        <div class="egg-panel-title-wrap">
            <span class="egg-panel-title-icon egg-panel-title-icon-green"><i class="fa fa-sitemap"></i></span>
            <div>
                <h3>Operational Flow</h3>
                <p>The standard production-to-sale process.</p>
            </div>
        </div>
    </div>
    <div class="egg-flow-track">
        <a href="{{ route('egg.production.index') }}" class="egg-flow-step"><span>1</span><strong>Collect</strong><small>Daily collection</small></a>
        <i class="fa fa-chevron-right egg-flow-arrow"></i>
        <a href="{{ route('egg.grading.index') }}" class="egg-flow-step"><span>2</span><strong>Grade</strong><small>Grade & pack</small></a>
        <i class="fa fa-chevron-right egg-flow-arrow"></i>
        <a href="{{ route('egg.stock.index') }}" class="egg-flow-step"><span>3</span><strong>Stock</strong><small>Available lots</small></a>
        <i class="fa fa-chevron-right egg-flow-arrow"></i>
        <a href="{{ route('egg.transfers.index') }}" class="egg-flow-step"><span>4</span><strong>Transfer</strong><small>Move stock</small></a>
        <i class="fa fa-chevron-right egg-flow-arrow"></i>
        <a href="{{ route('egg.sales.index') }}" class="egg-flow-step"><span>5</span><strong>Sell</strong><small>Customer sales</small></a>
    </div>
    <div class="egg-flow-note"><i class="fa fa-info-circle"></i> Purchased eggs enter stock directly. Every movement is recorded in the Egg stock ledger.</div>
</div>
