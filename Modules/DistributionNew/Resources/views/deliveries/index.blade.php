@extends('distributionnew::layouts.app')
@section('content')
@include('distributionnew::partials.erp-standard-styles')
<div class="pos-card"><div class="pos-card-header"><h4>@lang('distributionnew::messages.deliveries')</h4></div>
<table class="table table-bordered table-striped disnew-table"><thead><tr><th>Date</th><th>Invoice</th><th>Customer</th><th>Status</th><th>Action</th></tr></thead><tbody>
@foreach($deliveries as $delivery)<tr><td>{{ $delivery->delivery_date }}</td><td>{{ $delivery->disnew_sales_invoice_id }}</td><td>{{ $delivery->customer_id }}</td><td>{{ $delivery->status }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('distributionnew.deliveries.show',$delivery->id) }}">View</a></td></tr>@endforeach
</tbody></table>{{ $deliveries->links() }}</div>
@endsection
