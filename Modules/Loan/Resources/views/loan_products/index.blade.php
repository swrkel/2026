@extends('layouts.app')
@section('title', 'Loan Products')
@section('content')
@include('loan::loan_products._style')
<section class="content loan-product-page loan-table-wrapper">
    <div class="loan-page-header">
        <div class="loan-toolbar">
            <div>
                <h2><i class="fa fa-briefcase"></i> Loan Products</h2>
                <p>Loan product master data, amount limits, tenure rules, interest and charges</p>
            </div>
            <a href="{{ url('/loan/loan-products/create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add Loan Product</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row">
        <div class="col-md-3"><div class="loan-summary-card loan-summary-blue"><h3>{{ $products->count() }}</h3><span>Total Products</span></div></div>
        <div class="col-md-3"><div class="loan-summary-card loan-summary-green"><h3>{{ $products->where('status', 'active')->count() }}</h3><span>Active Products</span></div></div>
        <div class="col-md-3"><div class="loan-summary-card loan-summary-orange"><h3>{{ number_format((float)$products->avg('interest_rate'), 2) }}%</h3><span>Average Rate</span></div></div>
        <div class="col-md-3"><div class="loan-summary-card loan-summary-red"><h3>{{ $products->where('status', 'inactive')->count() }}</h3><span>Inactive Products</span></div></div>
    </div>

    <div class="loan-erp-card">
        <form method="GET" action="{{ url('/loan/loan-products') }}" class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Product name, code or description">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Product Category</label>
                    <select name="category_id" class="form-control select2">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3" style="padding-top:25px;">
                <button type="submit" class="btn btn-info"><i class="fa fa-search"></i> Search</button>
                <a href="{{ url('/loan/loan-products') }}" class="btn btn-default">Reset</a>
            </div>
        </form>
    </div>

    <div class="loan-erp-card">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width:130px;">Action</th>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th class="text-right">Min Amount</th>
                        <th class="text-right">Max Amount</th>
                        <th>Tenure</th>
                        <th class="text-right">Interest</th>
                        <th class="text-right">Processing Fee</th>
                        <th class="text-right">Late Charge</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">Actions <span class="caret"></span></button>
                                    <ul class="dropdown-menu">
                                        <li><a href="{{ url('/loan/loan-products/'.$product->id) }}"><i class="fa fa-eye"></i> View Product</a></li>
                                        <li><a href="{{ url('/loan/loan-products/'.$product->id.'/edit') }}"><i class="fa fa-edit"></i> Edit Product</a></li>
                                        <li class="divider"></li>
                                        <li>
                                            <form method="POST" action="{{ url('/loan/loan-products/'.$product->id.'/duplicate') }}" style="display:inline;">@csrf<button type="submit" class="btn btn-link" style="padding:3px 20px;color:#333;text-decoration:none;"><i class="fa fa-copy"></i> Duplicate</button></form>
                                        </li>
                                        @if($product->status == 'active')
                                            <li><form method="POST" action="{{ url('/loan/loan-products/'.$product->id.'/deactivate') }}" style="display:inline;">@csrf<button type="submit" class="btn btn-link" style="padding:3px 20px;color:#333;text-decoration:none;"><i class="fa fa-ban"></i> Deactivate</button></form></li>
                                        @else
                                            <li><form method="POST" action="{{ url('/loan/loan-products/'.$product->id.'/activate') }}" style="display:inline;">@csrf<button type="submit" class="btn btn-link" style="padding:3px 20px;color:#333;text-decoration:none;"><i class="fa fa-check"></i> Activate</button></form></li>
                                        @endif
                                        <li class="divider"></li>
                                        <li><form method="POST" action="{{ url('/loan/loan-products/'.$product->id.'/delete') }}" style="display:inline;" onsubmit="return confirm('Delete this loan product?');">@csrf<button type="submit" class="btn btn-link text-danger" style="padding:3px 20px;text-decoration:none;"><i class="fa fa-trash"></i> Delete</button></form></li>
                                    </ul>
                                </div>
                            </td>
                            <td>{{ $product->code ?: '-' }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ optional($product->category)->name ?: '-' }}</td>
                            <td class="text-right">{{ number_format((float)$product->minimum_amount, 2) }}</td>
                            <td class="text-right">{{ number_format((float)$product->maximum_amount, 2) }}</td>
                            <td>{{ ($product->minimum_loan_term ?: '-') . ' - ' . ($product->maximum_loan_term ?: '-') . ' ' . ucfirst($product->duration_type ?? '') }}</td>
                            <td class="text-right">{{ number_format((float)($product->default_interest_rate ?: $product->interest_rate), 2) }}%</td>
                            <td class="text-right">{{ number_format((float)($product->processing_fee ?? 0), 2) }}</td>
                            <td class="text-right">{{ number_format((float)($product->late_payment_charge ?? 0), 2) }}</td>
                            <td><span class="loan-badge {{ $product->status == 'active' ? 'loan-badge-active' : 'loan-badge-inactive' }}">{{ ucfirst($product->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center">No loan products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
