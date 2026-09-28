@extends('pos::layouts.app')
@section('pos_content')
<div class="box box-solid ch-card">
    <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-search"></i> Select Sale for Return</h3></div>
    <div class="box-body">
        <form method="get" action="{{ route('pos.returns.create') }}" class="row ch-filter-row">
            <div class="col-md-8"><select name="sale_id" class="form-control select2" required><option value="">Select completed sale</option>@foreach($sales as $s)<option value="{{ $s->id }}" @selected(request('sale_id') == $s->id)>{{ $s->sale_no ?? $s->invoice_no ?? ('POS-'.$s->id) }} - {{ $s->customer_name ?: 'Walk-in' }} - {{ number_format((float)($s->total_amount ?? 0), 4) }}</option>@endforeach</select></div>
            <div class="col-md-4"><button class="btn btn-primary"><i class="fa fa-refresh"></i> Load Sale</button> <a class="btn btn-default" href="{{ route('pos.returns.index') }}">Back</a></div>
        </form>
    </div>
</div>
@if($sale)
<form method="post" action="{{ route('pos.returns.store') }}" id="pos-return-form">
    @csrf
    <input type="hidden" name="sale_id" value="{{ $sale->id }}">
    <div class="box box-solid ch-card">
        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-undo"></i> Return Items - {{ $sale->sale_no ?? $sale->invoice_no ?? ('POS-'.$sale->id) }}</h3></div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3"><label>Return Date</label><input class="form-control" type="datetime-local" name="return_date" value="{{ now()->format('Y-m-d\TH:i') }}"></div>
                <div class="col-md-3"><label>Refund Method</label><select class="form-control" name="refund_method"><option value="cash">Cash Refund</option><option value="card">Card Refund</option><option value="credit_note">Credit Note</option><option value="mixed">Mixed Refund</option></select></div>
                <div class="col-md-3"><label>Approval</label><select class="form-control" name="approval_status"><option value="approved">Auto Approve</option><option value="pending">Need Manager Approval</option></select></div>
                <div class="col-md-3"><label>Note</label><input class="form-control" name="note" placeholder="Reason / reference"></div>
            </div>
            <hr>
            <div class="table-responsive"><table class="table table-hover ch-table"><thead><tr><th>Product</th><th class="text-right">Sold Qty</th><th class="text-right">Unit Price</th><th class="text-right">Return Qty</th><th class="text-right">Line Refund</th></tr></thead><tbody>
            @foreach($sale->lines as $line)
                <tr>
                    <td>{{ $line->product_name ?? ('Product #'.($line->product_id ?? '')) }}</td>
                    <td class="text-right">{{ number_format((float)$line->quantity, 3) }}</td>
                    <td class="text-right pos-return-unit">{{ number_format((float)$line->unit_price, 4, '.', '') }}</td>
                    <td><input class="form-control text-right pos-return-qty" type="number" step="0.001" min="0" max="{{ (float)$line->quantity }}" name="lines[{{ $line->id }}][quantity]" value="0"></td>
                    <td class="text-right pos-return-line-total">0.0000</td>
                </tr>
            @endforeach
            </tbody><tfoot><tr><th colspan="4" class="text-right">Refund Total</th><th class="text-right" id="pos-return-grand-total">0.0000</th></tr></tfoot></table></div>
            <div class="text-right"><button class="btn btn-success" type="submit"><i class="fa fa-save"></i> Save Return & Restore Stock</button></div>
        </div>
    </div>
</form>
@endif
@endsection
@section('pos_scripts')
<script>
(function(){function calc(){var total=0;document.querySelectorAll('#pos-return-form tbody tr').forEach(function(row){var qty=parseFloat((row.querySelector('.pos-return-qty')||{}).value||0);var price=parseFloat((row.querySelector('.pos-return-unit')||{}).textContent||0);var line=qty*price;total+=line;var cell=row.querySelector('.pos-return-line-total');if(cell)cell.textContent=line.toFixed(4);});var gt=document.getElementById('pos-return-grand-total');if(gt)gt.textContent=total.toFixed(4);}document.addEventListener('input',function(e){if(e.target.classList.contains('pos-return-qty'))calc();});calc();})();
</script>
@endsection
