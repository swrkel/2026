@extends('productsnew::layouts.app')
@section('productsnew_content')

<div class="productsnew-page">
    <div class="productsnew-header"><h1>Warranty Centre</h1><p>Register warranties and track service/replacement claims.</p></div>
    <div class="productsnew-card"><h4>Register Warranty</h4><form method="POST" action="{{ route('products-new.warranty.register') }}" class="productsnew-grid productsnew-grid-4">@csrf
        <select name="product_id" class="form-control pn-searchable-select" required>
                            <option value="">Select Active Product</option>
                            @foreach($lookups['productsNew'] ?? [] as $product)
                                <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>
                                    {{ $product->name }}{{ !empty($product->sku) ? ' — ' . $product->sku : '' }}
                                </option>
                            @endforeach
                        </select><input name="serial_id" class="form-control" placeholder="Serial ID"><input name="warranty_code" class="form-control" placeholder="Warranty Code"><input name="invoice_no" class="form-control" placeholder="Invoice No"><input type="date" name="warranty_start_date" class="form-control" required><input type="date" name="warranty_end_date" class="form-control" required><select name="status" class="form-control"><option value="active">Active</option><option value="expired">Expired</option><option value="void">Void</option></select><button class="btn productsnew-btn-primary">Register</button>
    </form></div>
    <div class="productsnew-card"><h4>New Claim</h4><form method="POST" action="{{ route('products-new.warranty.claim') }}" class="productsnew-grid productsnew-grid-4">@csrf
        <input name="registration_id" class="form-control" placeholder="Registration ID" required><input name="claim_no" class="form-control" placeholder="Claim No"><input type="date" name="claim_date" class="form-control" required><input name="claim_amount" class="form-control" placeholder="Claim Amount"><textarea name="fault_description" class="form-control" placeholder="Fault Description" required></textarea><button class="btn productsnew-btn-primary">Save Claim</button>
    </form></div>
    <div class="productsnew-card"><h4>Warranty Registrations</h4><table class="table table-bordered"><thead><tr><th>Code</th><th>Product</th><th>Serial</th><th>Start</th><th>End</th><th>Status</th></tr></thead><tbody>@foreach($registrations as $w)<tr><td>{{ $w->warranty_code }}</td><td>{{ $w->product_id }}</td><td>{{ $w->serial_id }}</td><td>{{ $w->warranty_start_date }}</td><td>{{ $w->warranty_end_date }}</td><td>{{ $w->status }}</td></tr>@endforeach</tbody></table>{{ $registrations->links() }}</div>
</div>

@endsection
