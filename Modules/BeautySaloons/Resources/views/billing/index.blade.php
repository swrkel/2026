@extends('layouts.app')
@section('title', __('beautysaloons::bs014.billings'))
@section('content')
<section class="content-header"><h1>{{ __('beautysaloons::bs014.billings') }}</h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive"><table class="table table-bordered table-striped" id="bs014_billing_table"><thead><tr><th>Date</th><th>Bill No</th><th>Customer</th><th>Total</th><th>Paid</th><th>Status</th><th>Action</th></tr></thead></table></div></div></section>
@endsection
