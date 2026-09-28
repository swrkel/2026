@extends('pos::layouts.app')
@section('title', $title ?? 'POS Sales')
@section('pos_content')
<div class="pos-hero sales-hero">
    <div>
        <span class="eyebrow">Standalone POS</span>
        <h2>Sales Workspace</h2>
        <p>Barcode, product search, cart, hold/resume sale, payment and receipt print are handled inside the POS module.</p>
    </div>
    <div class="hero-actions">
        <a class="btn btn-default" href="{{ route('pos.sales.list', [], false) }}">Sales List</a>
        <button class="btn btn-warning" type="button" onclick="POSSales.holdSale()">Hold Sale</button>
        <button class="btn btn-danger" type="button" onclick="POSSales.clearCart()">Clear</button>
    </div>
</div>

<div class="row pos-sales-grid">
    <div class="col-md-8">
        <div class="card pos-card prominent-card">
            <div class="card-header smart-card-header">
                <div><strong>Product Search</strong><small> Search by name, SKU, or scan barcode</small></div>
            </div>
            <div class="card-body">
                <div class="row compact-row">
                    <div class="col-md-8"><input type="text" id="pos_product_q" class="form-control pos-input-lg" placeholder="Search product / SKU / barcode"></div>
                    <div class="col-md-2"><button type="button" class="btn btn-primary btn-block" onclick="POSSales.searchProducts()">Search</button></div>
                    <div class="col-md-2"><button type="button" class="btn btn-success btn-block" onclick="POSSales.addBarcode()">Scan/Add</button></div>
                </div>
                <div id="pos_product_grid" class="product-grid">
                    @forelse($products as $product)
                        <button type="button" class="product-tile" onclick="POSSales.addLine({{ $product['id'] }}, {{ $product['sell_price'] }})">
                            <strong>{{ $product['name'] }}</strong>
                            <span>{{ $product['sku'] ?: $product['barcode'] }}</span>
                            <em>{{ number_format($product['sell_price'], 2) }}</em>
                            <small>Stock: {{ number_format($product['stock_quantity'], 3) }}</small>
                        </button>
                    @empty
                        <div class="empty-state">No POS products found. Add POS products or run the sample SQL.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card pos-card">
            <div class="card-header smart-card-header"><strong>Current Cart</strong><small id="cart_status">Ready</small></div>
            <div class="card-body no-pad">
                <div class="table-responsive">
                    <table class="table modern-table" id="pos_cart_table">
                        <thead><tr><th>Item</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Discount</th><th class="text-right">Tax</th><th class="text-right">Total</th><th></th></tr></thead>
                        <tbody>
                        @forelse($cart['lines'] ?? [] as $line)
                            <tr data-line="{{ $line->id }}"><td>{{ $line->product_id }}</td><td class="text-right">{{ number_format($line->quantity,3) }}</td><td class="text-right">{{ number_format($line->unit_price,2) }}</td><td class="text-right">{{ number_format($line->discount_amount,2) }}</td><td class="text-right">{{ number_format($line->tax_amount,2) }}</td><td class="text-right">{{ number_format($line->line_total,2) }}</td><td><button class="btn btn-danger btn-xs" onclick="POSSales.removeLine({{ $line->id }})">Remove</button></td></tr>
                        @empty
                            <tr><td colspan="7" class="text-center muted">Cart is empty</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card pos-card prominent-card">
            <div class="card-header smart-card-header"><strong>Customer & Payment</strong><small>Complete sale</small></div>
            <div class="card-body">
                <label>Customer</label>
                <div class="input-group-row">
                    <select id="customer_select" class="form-control" onchange="document.getElementById('customer_id').value=this.value;document.getElementById('customer_name').value=this.options[this.selectedIndex].text;">
                        <option value="">Walk-in Customer</option>
                        @foreach(($customers ?? collect()) as $pc)
                            @php
                                $posCustomerId = data_get($pc, 'id');
                                $posCustomerName = data_get($pc, 'name', 'Customer');
                                $posCustomerMobile = data_get($pc, 'mobile');
                            @endphp
                            <option value="{{ $posCustomerId }}" @selected((string) ($cart['customer_id'] ?? '') === (string) $posCustomerId)>
                                {{ $posCustomerName }}@if(!empty($posCustomerMobile)) - {{ $posCustomerMobile }}@endif
                            </option>
                        @endforeach
                    </select>
                    <button class="btn btn-default" type="button" onclick="POSSales.setCustomer()">Set</button>
                </div>
                <input type="hidden" id="customer_id" value="{{ $cart['customer_id'] ?? '' }}">
                <input type="hidden" id="customer_name" value="{{ $cart['customer_name'] ?? 'Walk-in Customer' }}">
                <small>For credit sales, select a POS customer first.</small>
                <form method="POST" action="{{ route('pos.sales.checkout', [], false) }}" class="payment-panel">
                    @csrf
                    <label>Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <option value="cash">Cash</option><option value="card">Card</option><option value="bank">Bank</option><option value="wallet">Wallet</option><option value="credit">Credit</option>
                    </select>
                    <label>Discount</label><input type="number" step="0.01" name="discount_amount" class="form-control" value="0">
                    <label>Tax</label><input type="number" step="0.01" name="tax_amount" class="form-control" value="0">
                    <label>Paid Amount</label><input type="number" step="0.01" name="paid_amount" id="paid_amount" class="form-control" value="{{ $cart['total_amount'] ?? 0 }}">
                    <label>Reference No</label><input type="text" name="reference_no" class="form-control">
                    <label>Note</label><textarea name="note" class="form-control" rows="2"></textarea>
                    <div class="bill-totals">
                        <div><span>Subtotal</span><strong id="sum_subtotal">{{ number_format($cart['subtotal'] ?? 0, 2) }}</strong></div>
                        <div><span>Discount</span><strong id="sum_discount">{{ number_format($cart['discount_amount'] ?? 0, 2) }}</strong></div>
                        <div><span>Tax</span><strong id="sum_tax">{{ number_format($cart['tax_amount'] ?? 0, 2) }}</strong></div>
                        <div class="grand"><span>Total</span><strong id="sum_total">{{ number_format($cart['total_amount'] ?? 0, 2) }}</strong></div>
                    </div>
                    <button class="btn btn-success btn-lg btn-block" type="submit">Save Sale & Print Receipt</button>
                </form>
            </div>
        </div>

        <div class="card pos-card">
            <div class="card-header smart-card-header"><strong>Held Sales</strong><small>Resume when customer returns</small></div>
            <div class="card-body held-list">
                @forelse($held as $h)
                    <div class="held-row"><span>#{{ $h->id }} {{ $h->customer_name ?: 'Walk-in' }}</span><button class="btn btn-primary btn-xs" onclick="POSSales.resume({{ $h->id }})">Resume</button></div>
                @empty
                    <div class="muted">No held sales.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('pos_scripts')
<script>
window.POS_SALES_ROUTES = {
    // IMPORTANT: keep these URLs relative.  In the multi-tenant ERP an absolute
    // route can be forced to APP_URL/the central host; fetch() would then lose
    // the active tenant session and the product search would fail.
    cart: '{{ route('pos.sales.cart.show', [], false) }}',
    addLine: '{{ route('pos.sales.cart.add_line', [], false) }}',
    removeLineBase: '/pos-module/sales-cart/line',
    clear: '{{ route('pos.sales.cart.clear', [], false) }}',
    customer: '{{ route('pos.sales.cart.customer', [], false) }}',
    hold: '{{ route('pos.sales.cart.hold', [], false) }}',
    resumeBase: '/pos-module/sales-cart',
    search: '{{ route('pos.sales.search_products', [], false) }}',
    barcode: '{{ route('pos.sales.barcode', [], false) }}'
};
</script>
@endsection
