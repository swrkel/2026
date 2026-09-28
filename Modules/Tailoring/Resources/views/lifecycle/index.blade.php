@extends('tailoring::layouts.app')
@section('title','Order Lifecycle')
@section('content')
@include('tailoring::partials.smart_toolbar', ['title'=>'Order Lifecycle'])
<div class="tailoring-lifecycle"><span>Customer</span><span>Measurement</span><span>Order</span><span>Job Card</span><span>Production</span><span>QC</span><span>Payment</span><span>Delivery</span></div>
@endsection
