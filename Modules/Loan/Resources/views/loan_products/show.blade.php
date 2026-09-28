@extends('layouts.app')
@section('title', 'Loan Product Details')
@section('content')
@include('loan::loan_products._style')
<section class="content loan-product-page">
    <div class="loan-page-header">
        <div class="loan-toolbar">
            <div>
                <h2><i class="fa fa-briefcase"></i> {{ $product->name }}</h2>
                <p>Loan Product Details</p>
            </div>
            <div>
                <a href="{{ url('/loan/loan-products/'.$product->id.'/edit') }}" class="btn btn-primary"><i class="fa fa-edit"></i> Edit</a>
                <a href="{{ url('/loan/loan-products') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="loan-erp-card">
                <div class="loan-erp-card-title"><i class="fa fa-info-circle"></i> Basic Information</div>
                <table class="table table-bordered loan-view-table">
                    <tr><th>Product Code</th><td>{{ $product->code ?: '-' }}</td></tr>
                    <tr><th>Product Name</th><td>{{ $product->name }}</td></tr>
                    <tr><th>Product Category</th><td>{{ optional($product->category)->name ?: '-' }}</td></tr>
                    <tr><th>Status</th><td><span class="loan-badge {{ $product->status == 'active' ? 'loan-badge-active' : 'loan-badge-inactive' }}">{{ ucfirst($product->status) }}</span></td></tr>
                    <tr><th>Description</th><td>{{ $product->description ?: '-' }}</td></tr>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <div class="loan-erp-card">
                <div class="loan-erp-card-title"><i class="fa fa-money"></i> Amount & Tenure Rules</div>
                <table class="table table-bordered loan-view-table">
                    <tr><th>Minimum Loan Amount</th><td class="text-right">{{ number_format((float)$product->minimum_amount, 2) }}</td></tr>
                    <tr><th>Maximum Loan Amount</th><td class="text-right">{{ number_format((float)$product->maximum_amount, 2) }}</td></tr>
                    <tr><th>Minimum Tenure</th><td>{{ $product->minimum_loan_term ?: '-' }} {{ ucfirst($product->duration_type ?? '') }}</td></tr>
                    <tr><th>Maximum Tenure</th><td>{{ $product->maximum_loan_term ?: '-' }} {{ ucfirst($product->duration_type ?? '') }}</td></tr>
                    <tr><th>Repayment Frequency</th><td>{{ ucfirst(str_replace('_', ' ', $product->repayment_frequency ?? '-')) }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="loan-erp-card">
                <div class="loan-erp-card-title"><i class="fa fa-percent"></i> Interest & Charges</div>
                <table class="table table-bordered loan-view-table">
                    <tr><th>Interest Method</th><td>{{ ucfirst(str_replace('_', ' ', $product->interest_method ?? '-')) }}</td></tr>
                    <tr><th>Interest Rate</th><td class="text-right">{{ number_format((float)($product->default_interest_rate ?: $product->interest_rate), 2) }}%</td></tr>
                    <tr><th>Minimum Interest Rate</th><td class="text-right">{{ number_format((float)$product->minimum_interest_rate, 2) }}%</td></tr>
                    <tr><th>Maximum Interest Rate</th><td class="text-right">{{ number_format((float)$product->maximum_interest_rate, 2) }}%</td></tr>
                    <tr><th>Processing Fee</th><td class="text-right">{{ number_format((float)($product->processing_fee ?? 0), 2) }}</td></tr>
                    <tr><th>Late Payment Charge</th><td class="text-right">{{ number_format((float)($product->late_payment_charge ?? 0), 2) }}</td></tr>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <div class="loan-erp-card">
                <div class="loan-erp-card-title"><i class="fa fa-sticky-note-o"></i> Notes</div>
                <p>{{ $product->notes ?: '-' }}</p>
            </div>
            <div class="loan-erp-card">
                <div class="loan-erp-card-title"><i class="fa fa-map-marker"></i> Business Locations</div>
                @forelse($product->locations as $location)
                    <span class="label label-info" style="display:inline-block;margin:3px;">{{ $location->name }}</span>
                @empty
                    <span class="loan-muted">No locations linked.</span>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
