@extends('layouts.app')
@section('title', 'MyHealth Billing & Claims')
@section('content')
<section class="content-header"><h1>MyHealth Billing & Claims</h1></section>
<section class="content">
@if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
<div class="row">
<div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-file-text-o"></i></span><div class="info-box-content"><span class="info-box-text">Today Invoices</span><span class="info-box-number">{{ $todayInvoices }}</span></div></div></div>
<div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-hourglass-half"></i></span><div class="info-box-content"><span class="info-box-text">Unpaid Invoices</span><span class="info-box-number">{{ $unpaidInvoices }}</span></div></div></div>
<div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-money"></i></span><div class="info-box-content"><span class="info-box-text">Today Payments</span><span class="info-box-number">{{ number_format($todayPayments, 4) }}</span></div></div></div>
<div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-purple"><i class="fa fa-shield"></i></span><div class="info-box-content"><span class="info-box-text">Pending Claims</span><span class="info-box-number">{{ $pendingClaims }}</span></div></div></div>
</div>
<a href="{{ route('myhealth.billing.services.index') }}" class="btn btn-primary">Billing Services</a>
<a href="{{ route('myhealth.billing.invoices.index') }}" class="btn btn-info">Invoices</a>
<a href="{{ route('myhealth.billing.claims.index') }}" class="btn btn-success">Claim Settlements</a>
</section>
@endsection
