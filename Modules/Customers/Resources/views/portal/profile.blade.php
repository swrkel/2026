@extends('customers::portal.layout')
@section('title', 'My Profile')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary')
    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">My Profile</h3></div>
        <div class="dd-card-body">
            <div class="row">
                <div class="col-md-6"><p><strong>Name:</strong> {{ $customer->name }}</p></div>
                <div class="col-md-6"><p><strong>Customer Code:</strong> {{ $customer->contact_id }}</p></div>
                <div class="col-md-6"><p><strong>Mobile:</strong> {{ $customer->mobile }}</p></div>
                <div class="col-md-6"><p><strong>Email:</strong> {{ $customer->email }}</p></div>
                <div class="col-md-12"><p><strong>Address:</strong> {{ trim(($customer->address ?? '').' '.($customer->address_2 ?? '').' '.($customer->city ?? '')) }}</p></div>
                <div class="col-md-6"><p><strong>Credit Limit:</strong> {{ is_null($customer->credit_limit) ? 'No Limit' : number_format((float)$customer->credit_limit, 2) }}</p></div>
                <div class="col-md-6"><p><strong>Status:</strong> {{ $customer->active ? 'Active' : 'Inactive' }}</p></div>
            </div>
        </div>
    </div>
</div>
@endsection
