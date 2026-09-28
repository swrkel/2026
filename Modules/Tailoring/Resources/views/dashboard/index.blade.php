@extends('tailoring::layouts.app')
@section('page_title', 'Tailoring Dashboard')
@section('tailoring_content')
<div class="row">
    @foreach(['Orders Today','Pending Job Cards','Ready for Delivery','Active Customers'] as $card)
        <div class="col-md-3"><div class="box box-primary"><div class="box-body text-center"><h4>{{ $card }}</h4><h2>0</h2></div></div></div>
    @endforeach
</div>
<div class="box box-solid"><div class="box-header"><h3 class="box-title">Quick Links</h3></div><div class="box-body">
    <a class="btn btn-primary" href="{{ route('tailoring.orders.index') }}">Orders</a>
    <a class="btn btn-info" href="{{ route('tailoring.job-cards.index') }}">Job Cards</a>
    <a class="btn btn-success" href="{{ route('tailoring.production.index') }}">Production</a>
    <a class="btn btn-warning" href="{{ route('tailoring.reports.index') }}">Reports</a>
</div></div>
@endsection
