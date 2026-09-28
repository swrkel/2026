@extends('layouts.app')
@section('title', 'Edit Loan Product')
@section('content')
<section class="content loan-product-page">
    <div class="loan-page-header">
        <h2><i class="fa fa-edit"></i> Edit Loan Product</h2>
        <p>{{ $product->name }}</p>
    </div>
    @include('loan::loan_products._form')
</section>
@endsection
