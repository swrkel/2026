@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/tailoring_customer_measurement.css') }}">
<div class="tailoring-clean-page"><div class="tailoring-page-header"><div><h1>Measurement Comparison</h1><p>Compare measurement versions for customer ID: {{ $customerId }}</p></div></div><div class="tailoring-card">No comparison data loaded.</div></div>
@endsection
