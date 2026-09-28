@extends('customers::portal.layout')
@section('title', 'Dealer Dashboard')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary')
    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Dealer Dashboard</h3></div>
        <div class="dd-card-body">
            <div class="row">
                <div class="col-md-3"><a class="dd-btn dd-btn-primary" style="width:100%;text-align:center;margin-bottom:10px;" href="{{ route('customers.portal.statement') }}">My Statement</a></div>
                <div class="col-md-3"><a class="dd-btn dd-btn-primary" style="width:100%;text-align:center;margin-bottom:10px;" href="{{ route('customers.portal.invoices') }}">My Invoices</a></div>
                <div class="col-md-3"><a class="dd-btn dd-btn-primary" style="width:100%;text-align:center;margin-bottom:10px;" href="{{ route('customers.portal.payments') }}">My Payments</a></div>
                <div class="col-md-3"><a class="dd-btn dd-btn-default" style="width:100%;text-align:center;margin-bottom:10px;" href="{{ route('customers.portal.profile') }}">My Profile</a></div>
                <div class="col-md-3"><a class="dd-btn dd-btn-primary" style="width:100%;text-align:center;margin-bottom:10px;" href="{{ route('customers.portal.notifications') }}">Notifications</a></div>
                <div class="col-md-3"><a class="dd-btn dd-btn-primary" style="width:100%;text-align:center;margin-bottom:10px;" href="{{ route('customers.portal.messages') }}">Messages</a></div>
                <div class="col-md-3"><a class="dd-btn dd-btn-primary" style="width:100%;text-align:center;margin-bottom:10px;" href="{{ route('customers.portal.announcements') }}">Announcements</a></div>
                <div class="col-md-3"><a class="dd-btn dd-btn-primary" style="width:100%;text-align:center;margin-bottom:10px;" href="{{ route('customers.portal.documents') }}">Documents</a></div>
                <div class="col-md-3"><a class="dd-btn dd-btn-primary" style="width:100%;text-align:center;margin-bottom:10px;" href="{{ route('customers.portal.analytics') }}">Analytics</a></div>
            </div>
            <hr>
            <p><strong>Last Payment Date:</strong> {{ !empty($summary['last_payment_date']) ? date('Y-m-d', strtotime($summary['last_payment_date'])) : '-' }}</p>
            <p><strong>Total Invoices:</strong> {{ $summary['invoice_count'] ?? 0 }} &nbsp; | &nbsp; <strong>Total Payments:</strong> {{ $summary['payment_count'] ?? 0 }}</p>
        </div>
    </div>
</div>
@endsection
