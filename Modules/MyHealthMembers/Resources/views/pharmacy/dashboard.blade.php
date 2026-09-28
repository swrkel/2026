@extends('layouts.app')
@section('title', 'MyHealth Pharmacy')
@section('content')
<section class="content-header"><h1>MyHealth Pharmacy</h1></section>
<section class="content">
    @if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <div class="row">
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-medkit"></i></span><div class="info-box-content"><span class="info-box-text">Medicines</span><span class="info-box-number">{{ $totalMedicines }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-warning"></i></span><div class="info-box-content"><span class="info-box-text">Low Stock</span><span class="info-box-number">{{ $lowStockMedicines }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-red"><i class="fa fa-calendar-times-o"></i></span><div class="info-box-content"><span class="info-box-text">Expiring Batches</span><span class="info-box-number">{{ $expiringBatches }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-list"></i></span><div class="info-box-content"><span class="info-box-text">Pending Dispense</span><span class="info-box-number">{{ $pendingDispenses }}</span></div></div></div>
    </div>
    <a href="{{ route('myhealth.pharmacy.medicines.index') }}" class="btn btn-primary">Medicine Master</a>
    <a href="{{ route('myhealth.pharmacy.stock.index') }}" class="btn btn-info">Stock Ledger</a>
    <a href="{{ route('myhealth.pharmacy.dispensing.index') }}" class="btn btn-success">Dispensing</a>
</section>
@endsection
