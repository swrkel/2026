@extends('customers::portal.layout')
@section('title', 'Favourite Products')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary', ['customer' => $customer, 'summary' => $summary])

    @if(session('status'))
        <div class="dd-card"><div class="dd-card-body"><strong>{{ session('status.msg') }}</strong></div></div>
    @endif

    <div class="dd-card">
        <div class="dd-card-header clearfix">
            <h3 class="dd-card-title pull-left">Favourite Products / Quick Order List</h3>
            <div class="pull-right dd-no-print">
                <a href="{{ route('customers.portal.orders.create') }}" class="dd-btn dd-btn-primary">Place Order</a>
            </div>
        </div>
        <div class="dd-card-body">
            <form method="GET" class="dd-filter">
                <div class="form-group">
                    <label>Search Product</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Product name or code">
                </div>
                <button type="submit" class="dd-btn dd-btn-default">Search</button>
            </form>
            <div class="dd-table-wrap">
                <table class="dd-table">
                    <thead>
                        <tr><th>Code</th><th>Product</th><th>Unit</th><th class="text-right">Available</th><th class="text-right">Price</th><th class="dd-no-print">Action</th></tr>
                    </thead>
                    <tbody>
                    @forelse($products as $product)
                        @php $isFav = in_array((int)$product->id, $favourites ?? [], true); @endphp
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td><strong>{{ $product->name }}</strong></td>
                            <td>{{ $product->unit }}</td>
                            <td class="text-right">{{ number_format((float)$product->available_stock, 3) }}</td>
                            <td class="text-right">{{ number_format((float)$product->unit_price, 2) }}</td>
                            <td class="dd-no-print">
                                @if($isFav)
                                    <form method="POST" action="{{ route('customers.portal.orders.favourites.remove', $product->id) }}" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dd-btn dd-btn-danger">Remove</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('customers.portal.orders.favourites.store') }}" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <button type="submit" class="dd-btn dd-btn-primary">Add Favourite</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No products found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
