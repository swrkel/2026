@extends('customers::portal.layout')
@section('title', 'Dealer Products')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary', ['customer' => $customer, 'summary' => $summary])

    <div class="dd-card">
        <div class="dd-card-header clearfix">
            <h3 class="dd-card-title pull-left">Product Catalog</h3>
            <div class="pull-right dd-no-print">
                <a href="{{ route('customers.portal.orders.create') }}" class="dd-btn dd-btn-primary">Place Order</a>
            </div>
        </div>
        <div class="dd-card-body">
            <form method="GET" action="{{ route('customers.portal.products') }}" class="dd-filter dd-no-print">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Product name or code">
                </div>
                <div class="form-group">
                    <button type="submit" class="dd-btn dd-btn-primary">Search</button>
                    <a href="{{ route('customers.portal.products') }}" class="dd-btn dd-btn-default">Clear</a>
                </div>
            </form>

            <div class="dd-table-wrap">
                <table class="dd-table">
                    <thead>
                        <tr>
                            <th>Product Code</th>
                            <th>Product Name</th>
                            <th>Unit</th>
                            <th class="text-right">Available Stock</th>
                            <th class="text-right">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->sku }}</td>
                                <td><strong>{{ $row->name }}</strong></td>
                                <td>{{ $row->unit }}</td>
                                <td class="text-right">{{ number_format((float)($row->available_stock ?? 0), 3) }}</td>
                                <td class="text-right">{{ number_format((float)($row->unit_price ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
