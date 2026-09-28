@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('modules/tailoring/css/tailoring_customer_measurement.css') }}">
<div class="tailoring-clean-page"><div class="tailoring-page-header"><div><h1>Measurement Centre</h1><p>Garment-wise measurement profiles, versions and comparison.</p></div><a href="{{ route('tailoring.measurement_centre.create') }}" class="btn btn-primary">Add Measurement Profile</a></div>
<div class="tailoring-kpi-grid"><div class="tailoring-kpi-card"><h4>Profiles</h4><strong>{{ $summary['profiles'] ?? 0 }}</strong></div><div class="tailoring-kpi-card"><h4>Versions</h4><strong>{{ $summary['versions'] ?? 0 }}</strong></div><div class="tailoring-kpi-card"><h4>Templates</h4><strong>{{ $summary['garment_templates'] ?? 0 }}</strong></div><div class="tailoring-kpi-card"><h4>Updated This Month</h4><strong>{{ $summary['updated_this_month'] ?? 0 }}</strong></div></div>
<div class="tailoring-card"><div class="tailoring-toolbar"><input type="text" class="form-control" placeholder="Search customer, garment, profile"><div><button class="btn btn-default">CSV</button><button class="btn btn-default">Excel</button><button class="btn btn-default">PDF</button><button class="btn btn-default">Print</button><button class="btn btn-default">Column Visibility</button></div></div></div></div>
@endsection
