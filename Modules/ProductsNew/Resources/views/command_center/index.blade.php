@extends('productsnew::layouts.app')
@section('title', __('productsnew::lang.product_command_center'))
@section('productsnew_content')
<section class="content-header productsnew-page-header">
    <h1>@lang('productsnew::lang.product_command_center')</h1>
    <p class="text-muted">@lang('productsnew::lang.command_center_subtitle')</p>
</section>
<section class="content productsnew-command-center">
    <div class="box productsnew-card">
        <div class="box-body">
            <form method="get" action="{{ route('products-new.command-center.index') }}" class="productsnew-command-search">
                <div class="input-group input-group-lg">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search by product, SKU, barcode, QR, OEM or supplier code">
                    <span class="input-group-btn"><button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Search</button></span>
                </div>
            </form>
        </div>
    </div>

    @if(!$workspace)
        <div class="box productsnew-card">
            <div class="box-header with-border"><h3 class="box-title">@lang('productsnew::lang.select_product')</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-hover productsnew-table">
                    <thead><tr><th>Product</th><th>SKU</th><th>Barcode</th><th>Status</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->barcode }}</td>
                            <td>{!! $product->is_inactive ? '<span class="label label-default">Inactive</span>' : '<span class="label label-success">Active</span>' !!}</td>
                            <td class="text-right"><a class="btn btn-sm btn-primary" href="{{ route('products-new.command-center.show', $product->id) }}">Open Command Center</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No matching products found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        @include('productsnew::command_center.partials.workspace', ['workspace' => $workspace])
    @endif
</section>
@endsection
