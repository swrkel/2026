@extends('layouts.app')
@section('title', 'Add Loan Product')
@section('content')
<section class="content loan-product-page">
    <div class="loan-page-header">
        <h2><i class="fa fa-briefcase"></i> Add Loan Product</h2>
        <p>Loan Module</p>
    </div>
    @include('loan::loan_products._form')
</section>
@endsection
