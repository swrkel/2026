@extends('pos::layouts.app')
@section('pos_content')
<div class="box box-solid ch-card">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-search"></i> Select Sale for Exchange</h3></div>
    <div class="box-body"><form method="get" action="{{ route('pos.exchanges.create') }}" class="row ch-filter-row"><div class="col-md-8"><select name="sale_id" class="form-control select2" required><option value="">Select completed sale</option>@foreach($sales as $s)<option value="{{ $s->id }}" @selected(request('sale_id') == $s->id)>{{ $s->sale_no ?? $s->invoice_no ?? ('POS-'.$s->id) }} - {{ $s->customer_name ?: 'Walk-in' }} - {{ number_format((float)($s->total_amount ?? 0), 4) }}</option>@endforeach</select></div><div class="col-md-4"><button class="btn btn-primary"><i class="fa fa-refresh"></i> Load Sale</button> <a class="btn btn-default" href="{{ route('pos.exchanges.index') }}">Back</a></div></form></div>
</div>
@if($sale)
<form method="post" action="{{ route('pos.exchanges.store') }}" id="pos-exchange-form">
@csrf
<input type="hidden" name="sale_id" value="{{ $sale->id }}">
<div class="row">
    <div class="col-md-6">
        <div class="box box-solid ch-card"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-undo"></i> Return Items</h3></div><div class="box-body table-responsive"><table class="table ch-table"><thead><tr><th>Product</th><th class="text-right">Sold</th><th class="text-right">Price</th><th class="text-right">Return Qty</th><th class="text-right">Total</th></tr></thead><tbody>
        @foreach($sale->lines as $line)
        <tr><td>{{ $line->product_name ?? ('Product #'.($line->product_id ?? '')) }}</td><td class="text-right">{{ number_format((float)$line->quantity,3) }}</td><td class="text-right ex-return-price">{{ number_format((float)$line->unit_price,4,'.','') }}</td><td><input class="form-control text-right ex-return-qty" type="number" step="0.001" min="0" max="{{ (float)$line->quantity }}" name="return_lines[{{ $line->id }}][quantity]" value="0"></td><td class="text-right ex-return-total">0.0000</td></tr>
        @endforeach
        </tbody><tfoot><tr><th colspan="4" class="text-right">Return Total</th><th class="text-right" id="ex-return-grand">0.0000</th></tr></tfoot></table></div></div>
    </div>
    <div class="col-md-6">
        <div class="box box-solid ch-card"><div class="box-header with-border"><h3 class="box-title"><i class="fa fa-shopping-cart"></i> Replacement Items</h3></div><div class="box-body"><div class="table-responsive"><table class="table ch-table"><thead><tr><th>Product</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr></thead><tbody>
        @for($i=0;$i<5;$i++)
        <tr><td><select class="form-control ex-new-product" name="new_lines[{{ $i }}][product_id]"><option value="">Select product</option>@foreach($products as $product)<option value="{{ $product->id }}" data-price="{{ (float)($product->selling_price ?? 0) }}">{{ $product->name }} @if($product->sku) - {{ $product->sku }} @endif</option>@endforeach</select></td><td><input class="form-control text-right ex-new-qty" type="number" step="0.001" min="0" name="new_lines[{{ $i }}][quantity]" value="0"></td><td><input class="form-control text-right ex-new-price" type="number" step="0.0001" min="0" name="new_lines[{{ $i }}][unit_price]" value="0"></td><td class="text-right ex-new-total">0.0000</td></tr>
        @endfor
        </tbody><tfoot><tr><th colspan="3" class="text-right">New Total</th><th class="text-right" id="ex-new-grand">0.0000</th></tr></tfoot></table></div></div></div>
    </div>
</div>
<div class="box box-solid ch-card"><div class="box-body"><div class="row"><div class="col-md-3"><label>Exchange Date</label><input class="form-control" type="datetime-local" name="exchange_date" value="{{ now()->format('Y-m-d\TH:i') }}"></div><div class="col-md-3"><label>Difference Payment Method</label><select class="form-control" name="payment_method"><option value="cash">Cash</option><option value="card">Card</option><option value="credit_note">Credit Note</option></select></div><div class="col-md-4"><label>Note</label><input class="form-control" name="note" placeholder="Exchange reason / reference"></div><div class="col-md-2"><label>Difference</label><h3 id="ex-difference" style="margin-top:5px">0.0000</h3></div></div><div class="text-right"><button class="btn btn-success"><i class="fa fa-save"></i> Save Exchange</button></div></div></div>
</form>
@endif
@endsection
@section('pos_scripts')
<script>
(function(){function calc(){var rt=0,nt=0;document.querySelectorAll('.ex-return-qty').forEach(function(inp){var row=inp.closest('tr'),qty=parseFloat(inp.value||0),price=parseFloat((row.querySelector('.ex-return-price')||{}).textContent||0),line=qty*price;rt+=line;row.querySelector('.ex-return-total').textContent=line.toFixed(4);});document.querySelectorAll('.ex-new-qty').forEach(function(inp){var row=inp.closest('tr'),qty=parseFloat(inp.value||0),price=parseFloat((row.querySelector('.ex-new-price')||{}).value||0),line=qty*price;nt+=line;row.querySelector('.ex-new-total').textContent=line.toFixed(4);});document.getElementById('ex-return-grand').textContent=rt.toFixed(4);document.getElementById('ex-new-grand').textContent=nt.toFixed(4);document.getElementById('ex-difference').textContent=(nt-rt).toFixed(4);}document.addEventListener('change',function(e){if(e.target.classList.contains('ex-new-product')){var row=e.target.closest('tr');var price=e.target.selectedOptions[0]?e.target.selectedOptions[0].getAttribute('data-price'):0;row.querySelector('.ex-new-price').value=parseFloat(price||0).toFixed(4);calc();}});document.addEventListener('input',function(e){if(e.target.className.indexOf('ex-')!==-1)calc();});calc();})();
</script>
@endsection
