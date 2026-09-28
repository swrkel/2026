@extends('productsnew::layouts.app')
@section('productsnew_content')

<div class="productsnew-grid productsnew-grid-2"><div class="productsnew-card"><h3>Recall Management</h3><p>Open and close recall cases by product/batch.</p><form method="POST" action="{{ route('products-new.recalls.store') }}">@csrf<label>Recall No</label><input name="recall_no" class="form-control" required><label>Product</label><select name="product_id" class="form-control pn-searchable-select" required>
                    <option value="">Select Active Product</option>
                    @foreach($lookups['productsNew'] ?? [] as $product)
                        <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>
                            {{ $product->name }}{{ !empty($product->sku) ? ' — ' . $product->sku : '' }}
                        </option>
                    @endforeach
                </select><label>Batch ID</label><input name="batch_id" class="form-control"><label>Reason</label><textarea name="reason" class="form-control" required></textarea><button class="btn btn-danger productsnew-mt">Open Recall</button></form></div><div class="productsnew-card"><h4>Recall Safety Notes</h4><ul class="productsnew-checklist"><li>Links recall with product timeline</li><li>Supports batch-specific recall</li><li>Tracks open/closed state</li><li>Ready for customer/supplier traceability in next reports</li></ul></div></div>
<div class="productsnew-card"><table class="table table-bordered"><thead><tr><th>Recall No</th><th>Product</th><th>Batch</th><th>Status</th><th>Reason</th><th>Started</th><th>Action</th></tr></thead><tbody>@foreach($recalls as $r)<tr><td>{{ $r->recall_no }}</td><td>{{ $r->product_id }}</td><td>{{ $r->batch_id }}</td><td>{{ $r->status }}</td><td>{{ $r->reason }}</td><td>{{ $r->started_at }}</td><td>@if($r->status != 'closed')<form method="POST" action="{{ route('products-new.recalls.close',$r->id) }}">@csrf<button class="btn btn-xs btn-success">Close</button></form>@endif</td></tr>@endforeach</tbody></table>{{ $recalls->links() }}</div>

@endsection
