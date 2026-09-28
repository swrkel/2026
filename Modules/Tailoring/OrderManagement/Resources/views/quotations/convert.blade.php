@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/order_management.css') }}">
<div class="tailoring-clean-page">
    <div class="tailoring-page-header"><div><h1>Convert Quotation to Order</h1><p>Confirm order, collect advance and create job cards.</p></div></div>
    <div class="tailoring-card">Converting quotation ID: {{ $id }}</div>
</div>
@endsection
