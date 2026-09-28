@extends('beautysaloons::layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('modules/beautysaloons/css/reports.css') }}">
<div class="container-fluid bs-reports-page">
    <div class="row">
        <div class="col-md-12">
            <h3>Beauty Saloons Reports & BI</h3>
            @include('beautysaloons::reports.partials.filters')
        </div>
    </div>

    <div class="row bs-kpi-row">
        @foreach($summary as $label => $value)
            <div class="col-md-3 col-sm-6">
                <div class="bs-kpi-card">
                    <div class="bs-kpi-label">{{ ucwords(str_replace('_', ' ', $label)) }}</div>
                    <div class="bs-kpi-value">{{ is_numeric($value) ? number_format($value, 2) : $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header with-border"><h4 class="box-title">Report Shortcuts</h4></div>
                <div class="box-body bs-report-shortcuts">
                    <a class="btn btn-default" href="{{ route('beautysaloons.reports-bi.appointments') }}">Appointments</a>
                    <a class="btn btn-default" href="{{ route('beautysaloons.reports-bi.sales') }}">Service Sales</a>
                    <a class="btn btn-default" href="{{ route('beautysaloons.reports-bi.retail-sales') }}">Product Sales</a>
                    <a class="btn btn-default" href="{{ route('beautysaloons.reports-bi.customers') }}">Customers</a>
                    <a class="btn btn-default" href="{{ route('beautysaloons.reports-bi.staff') }}">Staff</a>
                    <a class="btn btn-default" href="{{ route('beautysaloons.reports-bi.inventory') }}">Inventory</a>
                    <a class="btn btn-default" href="{{ route('beautysaloons.reports-bi.memberships') }}">Memberships</a>
                    <a class="btn btn-default" href="{{ route('beautysaloons.reports-bi.vouchers') }}">Gift Vouchers</a>
                    <a class="btn btn-default" href="{{ route('beautysaloons.reports-bi.loyalty') }}">Loyalty</a>
                    <a class="btn btn-default" href="{{ route('beautysaloons.reports-bi.payments') }}">Payments</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('modules/beautysaloons/js/reports.js') }}"></script>
@endsection
