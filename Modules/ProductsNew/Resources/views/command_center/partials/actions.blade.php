<div class="box productsnew-card productsnew-sticky-actions"><div class="box-header with-border"><h3 class="box-title">Command Actions</h3></div><div class="box-body">
<a href="{{ route('products-new.products.edit', $product->id) }}" class="btn btn-primary btn-block">Edit Product</a>
<a href="{{ route('products-new.barcode.index', ['product_id'=>$product->id]) }}" class="btn btn-info btn-block">Print Barcode</a>
<a href="{{ route('products-new.inventory-movements.index', ['product_id'=>$product->id]) }}" class="btn btn-warning btn-block">Stock Adjustment</a>
<a href="{{ route('products-new.products.timeline', $product->id) }}" class="btn btn-default btn-block">Audit Timeline</a>
<button class="btn btn-success btn-block productsnew-pcc-snapshot" data-url="{{ route('products-new.command-center.snapshot', $product->id) }}">Save Snapshot</button>
</div></div>
