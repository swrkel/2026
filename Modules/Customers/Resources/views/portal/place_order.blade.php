@extends('customers::portal.layout')
@section('title', 'Place Order')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary', ['customer' => $customer, 'summary' => $summary])

    @if(session('status'))
        <div class="dd-card"><div class="dd-card-body"><strong>{{ session('status.msg') }}</strong></div></div>
    @endif

    <form method="POST" action="{{ route('customers.portal.orders.store') }}" id="dealer_order_form">
        @csrf
        <div class="dd-card">
            <div class="dd-card-header clearfix">
                <h3 class="dd-card-title pull-left">Place New Order</h3>
                <div class="pull-right dd-no-print">
                    <a href="{{ route('customers.portal.orders') }}" class="dd-btn dd-btn-default">Back to Orders</a>
                </div>
            </div>
            <div class="dd-card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Order Date</label>
                            <input type="text" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Required Date</label>
                            <input type="date" name="required_date" class="form-control" value="{{ old('required_date') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Remarks</label>
                            <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}" placeholder="Optional remarks">
                        </div>
                    </div>
                </div>

                <div class="dd-table-wrap">
                    <table class="dd-table" id="dealer_order_lines">
                        <thead>
                            <tr>
                                <th style="min-width:330px;">Product</th>
                                <th class="text-right" style="min-width:130px;">Available</th>
                                <th class="text-right" style="min-width:130px;">Unit Price</th>
                                <th class="text-right" style="min-width:130px;">Quantity</th>
                                <th class="text-right" style="min-width:140px;">Line Total</th>
                                <th style="min-width:170px;">Remarks</th>
                                <th class="dd-no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select name="product_id[]" class="form-control js-product" required>
                                        <option value="">Please Select</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" data-price="{{ (float)$product->unit_price }}" data-stock="{{ (float)$product->available_stock }}">
                                                {{ $product->sku ? $product->sku . ' - ' : '' }}{{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-right js-stock">0.000</td>
                                <td class="text-right js-price">0.00</td>
                                <td><input type="number" step="0.0001" min="0.0001" name="quantity[]" class="form-control text-right js-qty" required></td>
                                <td class="text-right js-line-total">0.00</td>
                                <td><input type="text" name="line_remarks[]" class="form-control"></td>
                                <td class="dd-no-print"><button type="button" class="dd-btn dd-btn-danger js-remove-line">Remove</button></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">Total</th>
                                <th class="text-right" id="order_total_qty">0.0000</th>
                                <th class="text-right" id="order_total_amount">0.00</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="dd-no-print" style="margin-top:16px;">
                    <button type="button" class="dd-btn dd-btn-default" id="add_order_line">Add Product</button>
                    <button type="submit" class="dd-btn dd-btn-primary">Submit Order</button>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
(function(){
    function fmt(n, d){ return (Number(n || 0)).toFixed(d); }
    function recalc(){
        var totalQty = 0, totalAmount = 0;
        document.querySelectorAll('#dealer_order_lines tbody tr').forEach(function(row){
            var select = row.querySelector('.js-product');
            var qty = Number(row.querySelector('.js-qty').value || 0);
            var price = Number(select.options[select.selectedIndex] ? select.options[select.selectedIndex].dataset.price || 0 : 0);
            var stock = Number(select.options[select.selectedIndex] ? select.options[select.selectedIndex].dataset.stock || 0 : 0);
            var lineTotal = qty * price;
            row.querySelector('.js-stock').innerText = fmt(stock, 3);
            row.querySelector('.js-price').innerText = fmt(price, 2);
            row.querySelector('.js-line-total').innerText = fmt(lineTotal, 2);
            totalQty += qty;
            totalAmount += lineTotal;
        });
        document.getElementById('order_total_qty').innerText = fmt(totalQty, 4);
        document.getElementById('order_total_amount').innerText = fmt(totalAmount, 2);
    }
    document.addEventListener('change', function(e){ if(e.target.classList.contains('js-product')) recalc(); });
    document.addEventListener('input', function(e){ if(e.target.classList.contains('js-qty')) recalc(); });
    document.getElementById('add_order_line').addEventListener('click', function(){
        var tbody = document.querySelector('#dealer_order_lines tbody');
        var tr = tbody.querySelector('tr').cloneNode(true);
        tr.querySelectorAll('input').forEach(function(i){ i.value = ''; });
        tr.querySelector('select').value = '';
        tr.querySelector('.js-stock').innerText = '0.000';
        tr.querySelector('.js-price').innerText = '0.00';
        tr.querySelector('.js-line-total').innerText = '0.00';
        tbody.appendChild(tr);
        recalc();
    });
    document.addEventListener('click', function(e){
        if(e.target.classList.contains('js-remove-line')){
            var rows = document.querySelectorAll('#dealer_order_lines tbody tr');
            if(rows.length > 1){ e.target.closest('tr').remove(); recalc(); }
        }
    });
    document.getElementById('dealer_order_form').addEventListener('submit', function(e){
        var ok = false;
        document.querySelectorAll('#dealer_order_lines tbody tr').forEach(function(row){
            if(row.querySelector('.js-product').value && Number(row.querySelector('.js-qty').value || 0) > 0){ ok = true; }
        });
        if(!ok){ e.preventDefault(); alert('Please add at least one product with quantity.'); }
    });
    recalc();
})();
</script>
@endsection
