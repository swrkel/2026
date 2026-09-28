@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/order_management.css') }}">
<div class="tailoring-clean-page">
    <div class="tailoring-page-header"><div><h1>Job Card View</h1><p>Measurements, materials, workflow, notes, QC and production timeline.</p></div><a href="{{ route('tailoring.job_cards.index') }}" class="btn btn-default">Back</a></div>
    <div class="tailoring-card">Job card details.</div>
</div>
@endsection
