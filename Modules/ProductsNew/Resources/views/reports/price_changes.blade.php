@extends('productsnew::layouts.app')

@section('productsnew_page_title', 'Price Change Report')
@section('productsnew_page_subtitle', 'Audit product purchasing and selling price changes.')

@section('productsnew_content')
<div class="productsnew-page">
    <div class="productsnew-header">
        <div>
            <h2>Price Change History</h2>
            <p>Historical product price changes recorded by Products New.</p>
        </div>
    </div>

    <div class="productsnew-table-card">
        <div class="table-responsive">
            <table class="table table-bordered table-striped productsnew-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price Type</th>
                        <th class="text-right">Old Price</th>
                        <th class="text-right">New Price</th>
                        <th>Effective Date</th>
                        <th>Changed By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->product_name ?: ('Product #' . ($row->product_id ?? '-')) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $row->price_type ?? 'selling')) }}</td>
                            <td class="text-right">{{ number_format((float) ($row->old_price ?? 0), 4) }}</td>
                            <td class="text-right">{{ number_format((float) ($row->new_price ?? 0), 4) }}</td>
                            <td>{{ $row->effective_from ?? $row->created_at ?? '-' }}</td>
                            <td>{{ $row->created_by ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No price changes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($rows, 'links'))
            {{ $rows->links() }}
        @endif
    </div>
</div>
@endsection
