@extends('distributionnew::layouts.app', ['title' => 'Create Sales Order'])
@section('content')<div class="ch-card"><div class="ch-card-header"><h3 class="ch-card-title">Create Sales Order</h3></div><div class="ch-card-body"><form method="post" action="{{ route('distributionnew.sales-orders.store') }}">@include('distributionnew::sales_orders.form')</form></div></div>@endsection
