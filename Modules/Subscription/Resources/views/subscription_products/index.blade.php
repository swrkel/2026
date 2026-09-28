@extends('layouts.app')

@section('title', 'Subscription Products')

@section('content')
<section class="content-header">
    <h1>Subscription Products</h1>
</section>

<section class="content">

    {{-- ADD FORM --}}
    @can('subscription_product.create')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Add Subscription Product</h3>
                </div>

                <form method="POST" action="{{ route('subscription-products.store') }}">
                    @csrf

                    <div class="box-body">
                        <div class="row">

                            {{-- Subscription Product --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Subscription Product <span class="text-danger">*</span></label>
                                    <input type="text" name="subscription_product" class="form-control" required>
                                </div>
                            </div>

                            {{-- Base Amount --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Base Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="base_amount" class="form-control" required>
                                </div>
                            </div>

                            {{-- Subscription Period --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Subscription Period <span class="text-danger">*</span></label>
                                    <select name="subscription_period" class="form-control" required>
                                        <option value="">Select</option>
                                        <option value="daily">daily</option>
                                        <option value="weekly">weekly</option>
                                        <option value="monthly">monthly</option>
                                        <option value="quarterly">quarterly</option>
                                        <option value="bi_annually">bi-annually</option>
                                        <option value="annually">annually</option>
                                        
                                    </select>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="box-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    {{-- LISTING --}}
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">Subscription Products List</h3>
                </div>

                <div class="box-body table-responsive">
                    <table class="table table-bordered table-striped" id="subscription_products_table">
                        <thead>
                            <tr>
                                <th>@lang( 'subscription::lang.date_time' )</th>
                                <th>@lang( 'subscription::lang.code' )</th>
                                <th>@lang( 'subscription::lang.subscription_product' )</th>
                                <th>@lang( 'subscription::lang.base_amount' )</th>
                                <th>@lang( 'subscription::lang.subscription_period' )</th>
                                <th>@lang( 'subscription::lang.created_by' )</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($product->created_at)->format('d-m-Y H:i') }}</td>
                                    <td>{{ $product->code }}</td>
                                    <td>{{ $product->subscription_product }}</td>
                                    <td>{{ number_format($product->price, 2) }}</td>
                                    <td>{{ $product->subscription_period }}</td>
                                    <td>{{ $product->created_user ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">
                                        No subscription products found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

</section>
@endsection
@section('javascript')
<script>
$(document).ready(function () {

    $('#subscription_products_table').DataTable({
        processing: true,
        responsive: false,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [3], className: 'text-right' }
        ]
    });

});
</script>
@endsection
