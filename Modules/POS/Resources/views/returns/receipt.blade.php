@extends('pos::layouts.app')
@section('pos_content')
<div class="box box-solid ch-card pos-print-card">
    <div class="box-header with-border ch-card-header"><h3 class="box-title"><i class="fa fa-print"></i> Return Receipt</h3><div class="box-tools pull-right"><button class="btn btn-primary btn-sm" onclick="window.print()"><i class="fa fa-print"></i> Print</button><a class="btn btn-default btn-sm" href="{{ route('pos.returns.index') }}">Back</a></div></div>
    <div class="box-body">
        <div class="row"><div class="col-md-6"><h2 style="margin-top:0">{{ $receipt->return_no }}</h2><p><strong>Sale:</strong> {{ $receipt->sale_no ?? $receipt->invoice_no }}<br><strong>Customer:</strong> {{ $receipt->customer_name ?: 'Walk-in' }}<br><strong>Date:</strong> {{ $receipt->return_date }}</p></div><div class="col-md-6 text-right"><p><strong>Refund Method:</strong> {{ ucfirst(str_replace('_',' ', $receipt->refund_method ?? 'cash')) }}<br><strong>Status:</strong> {{ ucfirst($receipt->status ?? 'final') }}<br><strong>Approval:</strong> {{ ucfirst($receipt->approval_status ?? 'approved') }}</p></div></div>
        <div class="table-responsive"><table class="table table-bordered ch-table"><thead><tr><th>Product</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Total</th></tr></thead><tbody>@foreach($receipt->lines as $line)<tr><td>{{ $line->product_name }}</td><td class="text-right">{{ number_format((float)$line->quantity,3) }}</td><td class="text-right">{{ number_format((float)$line->unit_price,4) }}</td><td class="text-right">{{ number_format((float)$line->line_total,4) }}</td></tr>@endforeach</tbody><tfoot><tr><th colspan="3" class="text-right">Refund Total</th><th class="text-right">{{ number_format((float)$receipt->total_amount,4) }}</th></tr></tfoot></table></div>
        @if(!empty($receipt->note))<p><strong>Note:</strong> {{ $receipt->note }}</p>@endif
    </div>
</div>
@endsection
