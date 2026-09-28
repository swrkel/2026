@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/order_management.css') }}">
<div class="tailoring-clean-page">
    <div class="tailoring-page-header"><div><h1>Quotation View</h1><p>Review quotation and convert to order.</p></div><a href="{{ route('tailoring.quotations.index') }}" class="btn btn-default">Back</a></div>
    <div class="tailoring-card">Quotation details.</div>
</div>
@endsection
