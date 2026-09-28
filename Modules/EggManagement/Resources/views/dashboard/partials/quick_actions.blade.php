<div class="egg-pos-panel egg-quick-panel">
    <div class="egg-pos-panel-head">
        <div class="egg-panel-title-wrap">
            <span class="egg-panel-title-icon egg-panel-title-icon-blue"><i class="fa fa-bolt"></i></span>
            <div>
                <h3>Quick Actions</h3>
                <p>Common day-to-day Egg Management tasks.</p>
            </div>
        </div>
    </div>

    <div class="egg-quick-grid">
        <a class="egg-quick-tile egg-quick-blue" href="{{ route('egg.production.create') }}">
            <span class="egg-quick-icon"><i class="fa fa-plus-circle"></i></span>
            <span class="egg-quick-copy"><strong>Add Collection</strong><small>Record today's egg collection</small></span>
            <i class="fa fa-chevron-right egg-quick-arrow"></i>
        </a>
        <a class="egg-quick-tile egg-quick-green" href="{{ route('egg.grading.create') }}">
            <span class="egg-quick-icon"><i class="fa fa-th-large"></i></span>
            <span class="egg-quick-copy"><strong>Grade Eggs</strong><small>Grade and move good eggs to stock</small></span>
            <i class="fa fa-chevron-right egg-quick-arrow"></i>
        </a>
        <a class="egg-quick-tile egg-quick-purple" href="{{ route('egg.sales.create') }}">
            <span class="egg-quick-icon"><i class="fa fa-shopping-cart"></i></span>
            <span class="egg-quick-copy"><strong>New Sale</strong><small>Sell available egg stock</small></span>
            <i class="fa fa-chevron-right egg-quick-arrow"></i>
        </a>
        <a class="egg-quick-tile egg-quick-orange" href="{{ route('egg.purchases.create') }}">
            <span class="egg-quick-icon"><i class="fa fa-truck"></i></span>
            <span class="egg-quick-copy"><strong>New Purchase</strong><small>Receive eggs from a supplier</small></span>
            <i class="fa fa-chevron-right egg-quick-arrow"></i>
        </a>
        <a class="egg-quick-tile egg-quick-cyan" href="{{ route('egg.transfers.create') }}">
            <span class="egg-quick-icon"><i class="fa fa-exchange"></i></span>
            <span class="egg-quick-copy"><strong>Stock Transfer</strong><small>Move stock between locations / stores</small></span>
            <i class="fa fa-chevron-right egg-quick-arrow"></i>
        </a>
        <a class="egg-quick-tile egg-quick-red" href="{{ route('egg.adjustments.create') }}">
            <span class="egg-quick-icon"><i class="fa fa-sliders"></i></span>
            <span class="egg-quick-copy"><strong>Adjustment / Wastage</strong><small>Record breakage or stock correction</small></span>
            <i class="fa fa-chevron-right egg-quick-arrow"></i>
        </a>
    </div>
</div>
