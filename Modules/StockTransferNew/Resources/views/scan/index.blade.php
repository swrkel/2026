@extends('stocktransfernew::layouts.app')
@section('content')
@include('stocktransfernew::partials.header', ['title' => 'Barcode Scan Transfer', 'subtitle' => 'Scan Products module barcodes/SKUs and add lines to a transfer draft.'])
<div class="stn-card"><h4>Scan Product</h4><p class="text-muted">This page only looks up products from the existing Products module. It does not create or duplicate product records.</p><div class="row"><div class="col-md-6"><input id="stn_barcode" class="form-control" placeholder="Scan or type barcode / SKU"></div><div class="col-md-2"><button id="stn_scan_btn" class="btn btn-primary btn-block">Add Line</button></div></div><hr><table class="table table-bordered" id="stn_scan_lines"><thead><tr><th>SKU</th><th>Product</th><th class="text-right">Qty</th><th class="text-right">Unit Cost</th></tr></thead><tbody></tbody></table></div>
@endsection
