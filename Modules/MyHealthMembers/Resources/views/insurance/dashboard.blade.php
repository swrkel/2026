@extends('layouts.app')
@section('title', 'MyHealth Insurance')
@section('content')
<section class="content-header"><h1>MyHealth Insurance</h1></section>
<section class="content">
@if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
<div class="row">
<div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-building"></i></span><div class="info-box-content"><span class="info-box-text">Companies</span><span class="info-box-number">{{ $totalCompanies }}</span></div></div></div>
<div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-id-card"></i></span><div class="info-box-content"><span class="info-box-text">Active Policies</span><span class="info-box-number">{{ $activePolicies }}</span></div></div></div>
<div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-file-text-o"></i></span><div class="info-box-content"><span class="info-box-text">Submitted Claims</span><span class="info-box-number">{{ $submittedClaims }}</span></div></div></div>
<div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-purple"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text">Approved Claims</span><span class="info-box-number">{{ $approvedClaims }}</span></div></div></div>
</div>
<a href="{{ route('myhealth.insurance.companies.index') }}" class="btn btn-primary">Insurance Companies</a>
<a href="{{ route('myhealth.insurance.policies.index') }}" class="btn btn-info">Member Policies</a>
<a href="{{ route('myhealth.insurance.claims.index') }}" class="btn btn-success">Claims</a>
</section>
@endsection
