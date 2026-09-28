@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/order_management.css') }}">
<div class="tailoring-clean-page">
    <div class="tailoring-page-header"><div><h1>Order 360°</h1><p>Items, job cards, payments, production timeline and delivery.</p></div><a href="{{ route('tailoring.orders.index') }}" class="btn btn-default">Back</a></div>
    <div class="tailoring-card">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#summary" data-toggle="tab">Summary</a></li>
            <li><a href="#items" data-toggle="tab">Items</a></li>
            <li><a href="#jobcards" data-toggle="tab">Job Cards</a></li>
            <li><a href="#payments" data-toggle="tab">Payments</a></li>
            <li><a href="#timeline" data-toggle="tab">Timeline</a></li>
            <li><a href="#delivery" data-toggle="tab">Delivery</a></li>
        </ul>
        <div class="tab-content" style="padding-top:15px;"><div class="tab-pane active" id="summary">Order summary.</div></div>
    </div>
</div>
@endsection
