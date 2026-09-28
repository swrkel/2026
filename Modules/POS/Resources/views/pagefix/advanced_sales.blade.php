@extends('pos::layouts.app', ['title' => $title ?? 'POS Advanced Sales'])

@section('pos_page_description', 'Use the advanced sales workspace with fast product search, customer selection and checkout tools.')
@section('pos_page_actions')
    <a href="{{ url('/pos-module') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
    <a href="{{ url('/pos-module/sales/workspace') }}" class="btn btn-primary btn-sm"><i class="fa fa-shopping-cart"></i> Open Workspace</a>
    <a href="{{ url('/pos-module/sales/list') }}" class="btn btn-success btn-sm"><i class="fa fa-list"></i> Sales List</a>
@endsection

@section('pos_content')
@php
    $cards = [
        ['label' => 'Workspace', 'value' => 'Ready', 'icon' => 'fa-shopping-cart', 'hint' => 'Advanced sales available', 'tone' => '', 'url' => url('/pos-module/sales/workspace')],
        ['label' => 'Product Search', 'value' => 'Live', 'icon' => 'fa-search', 'hint' => 'Name, SKU or barcode', 'tone' => 'success'],
        ['label' => 'Customer Selection', 'value' => 'Ready', 'icon' => 'fa-user', 'hint' => 'Customer and walk-in sales', 'tone' => 'warning'],
        ['label' => 'Checkout', 'value' => 'Ready', 'icon' => 'fa-credit-card', 'hint' => 'Payment and receipt', 'tone' => 'purple'],
    ];
@endphp
<div class="ch-kpi-grid ch-standard-grid">
    @foreach($cards as $card)
        @include('pos::pagefix.partials.kpi-card', ['card' => $card])
    @endforeach
</div>

<div class="ch-card">
    <div class="ch-card-header">
        <div><h3 class="ch-card-title"><i class="fa fa-cogs text-primary"></i> Advanced Sales Workspace</h3><div class="ch-card-subtitle">Search products and continue directly to the full sales workspace.</div></div>
        <div class="ch-quick-actions">
            <a class="btn btn-primary btn-sm" href="{{ url('/pos-module/sales/workspace') }}"><i class="fa fa-shopping-cart"></i> Open Sales Workspace</a>
            <a class="btn btn-default btn-sm" href="{{ url('/pos-module/sales/list') }}"><i class="fa fa-list"></i> Sales List</a>
        </div>
    </div>
    <div class="ch-card-body">
        <div class="ch-toolbar">
            <div style="position:relative;min-width:280px;max-width:620px;flex:1;">
                <input type="text" id="pos-advanced-product-search" class="form-control" placeholder="Type product name, SKU or barcode" style="padding-right:42px;">
                <i class="fa fa-search" style="position:absolute;right:15px;top:13px;color:#64748b;"></i>
            </div>
            <div>
                <button type="button" class="btn btn-primary" id="pos-advanced-search-button"><i class="fa fa-search"></i> Search Products</button>
            </div>
        </div>
        <div class="empty-state" id="pos-advanced-sale-screen">
            <i class="fa fa-shopping-cart fa-3x"></i>
            <h4>Advanced Sales is ready</h4>
            <p>Enter a product name, SKU or barcode above, or open the complete sales workspace.</p>
            <a class="btn btn-primary" href="{{ url('/pos-module/sales/workspace') }}"><i class="fa fa-arrow-right"></i> Continue to Sales</a>
        </div>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header"><div><h3 class="ch-card-title"><i class="fa fa-bolt text-warning"></i> Quick Operations</h3><div class="ch-card-subtitle">Common sales actions using the same POS dashboard controls.</div></div></div>
    <div class="ch-card-body pos-quick-operations">
        <a href="{{ url('/pos-module/sales/workspace') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-shopping-cart"></i></span><span><strong>New Sale</strong><span>Create a new transaction</span></span></a>
        <a href="{{ url('/pos-module/sales/list') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-list"></i></span><span><strong>Sales List</strong><span>Review completed sales</span></span></a>
        <a href="{{ url('/pos-module/returns') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-undo"></i></span><span><strong>Returns</strong><span>Process sales returns</span></span></a>
        <a href="{{ url('/pos-module/customers') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-users"></i></span><span><strong>Customers</strong><span>Select or add customer</span></span></a>
        <a href="{{ url('/pos-module/products') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-cube"></i></span><span><strong>Products</strong><span>Browse POS products</span></span></a>
        <a href="{{ url('/pos-module/reports') }}" class="pos-quick-operation"><span class="qo-icon"><i class="fa fa-bar-chart"></i></span><span><strong>Reports</strong><span>View sales reports</span></span></a>
    </div>
</div>
@endsection

@section('pos_scripts')
<script>
(function () {
    var button = document.getElementById('pos-advanced-search-button');
    var input = document.getElementById('pos-advanced-product-search');
    if (!button || !input) return;
    function openWorkspace() {
        var query = (input.value || '').trim();
        window.location.href = '{{ url('/pos-module/sales/workspace') }}' + (query ? '?q=' + encodeURIComponent(query) : '');
    }
    button.addEventListener('click', openWorkspace);
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') { event.preventDefault(); openWorkspace(); }
    });
})();
</script>
@endsection
