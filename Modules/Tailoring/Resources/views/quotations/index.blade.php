@extends('tailoring::layouts.app')
@section('title','Quotations')
@section('content')
@include('tailoring::partials.smart_toolbar', ['title'=>'Quotations','createRoute'=>route('tailoring.quotations.create')])
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>#</th><th>Quotation No</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Action</th></tr></thead><tbody>@foreach($quotations as $quotation)<tr><td>{{ $quotation->id }}</td><td>{{ $quotation->quotation_no }}</td><td>{{ $quotation->customer_id }}</td><td>{{ optional($quotation->quotation_date)->format('Y-m-d') }}</td><td>{{ data_get($quotation->totals,'total',0) }}</td><td>{{ $quotation->status }}</td><td><form method="post" action="{{ route('tailoring.quotations.convert', $quotation) }}">@csrf<button class="btn btn-xs btn-success">Convert to Order</button></form></td></tr>@endforeach</tbody></table></div>{{ $quotations->links() }}
@endsection
