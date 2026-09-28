@if(Route::has('digitalwallet.dashboard'))
<li class="treeview {{ request()->is('digital-wallet*') ? 'active' : '' }}">
    <a href="#"><i class="fa fa-wallet"></i> <span>Digital Wallet</span> <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
    <ul class="treeview-menu">
        @if(Route::has('digitalwallet.dashboard'))<li><a href="{{ route('digitalwallet.dashboard') }}"><i class="fa fa-dashboard"></i> Dashboard</a></li>@endif
        @if(Route::has('digitalwallet.wallets.index'))<li><a href="{{ route('digitalwallet.wallets.index') }}"><i class="fa fa-credit-card"></i> Wallets</a></li>@endif
        @if(Route::has('digitalwallet.hierarchy.index'))<li><a href="{{ route('digitalwallet.hierarchy.index') }}"><i class="fa fa-sitemap"></i> Hierarchy</a></li>@endif
        @if(Route::has('digitalwallet.types.index'))<li><a href="{{ route('digitalwallet.types.index') }}"><i class="fa fa-tags"></i> Wallet Types</a></li>@endif
        @if(Route::has('digitalwallet.transfers.index'))<li><a href="{{ route('digitalwallet.transfers.index') }}"><i class="fa fa-exchange"></i> Transfers</a></li>@endif
        @if(Route::has('digitalwallet.financial.index'))<li><a href="{{ route('digitalwallet.financial.index') }}"><i class="fa fa-university"></i> Financial Engine</a></li>@endif
        @if(Route::has('digitalwallet.approvals.index'))<li><a href="{{ route('digitalwallet.approvals.index') }}"><i class="fa fa-check-square-o"></i> Approvals</a></li>@endif
        @if(Route::has('digitalwallet.ledger.index'))<li><a href="{{ route('digitalwallet.ledger.index') }}"><i class="fa fa-book"></i> Ledger</a></li>@endif
        @if(Route::has('digitalwallet.transactions.index'))<li><a href="{{ route('digitalwallet.transactions.index') }}"><i class="fa fa-list"></i> Transactions</a></li>@endif
        @if(Route::has('digitalwallet.rules.index'))<li><a href="{{ route('digitalwallet.rules.index') }}"><i class="fa fa-cogs"></i> Rules</a></li>@endif
        @if(Route::has('digitalwallet.reports.index'))<li><a href="{{ route('digitalwallet.reports.index') }}"><i class="fa fa-bar-chart"></i> Reports</a></li>@endif
        @if(Route::has('digitalwallet.settings.index'))<li><a href="{{ route('digitalwallet.settings.index') }}"><i class="fa fa-sliders"></i> Settings</a></li>@endif
    </ul>
</li>
@endif
