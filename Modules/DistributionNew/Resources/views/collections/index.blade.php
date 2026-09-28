@extends('distributionnew::layouts.app')
@section('content')
<div class="disnew-page">

@include('distributionnew::partials.erp-standard-styles')
<div class="disnew-card"><div class="disnew-card-header"><h3>Collections</h3><a class="btn btn-primary" href="{{ route('distribution-new.collections.create') }}">Add Collection</a></div>
<table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Date</th><th>Customer</th><th>Invoice</th><th>Method</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead><tbody>
@foreach($collections as $row)<tr><td>{{ $row->collection_no }}</td><td>{{ $row->collection_date }}</td><td>{{ $row->customer_id }}</td><td>{{ $row->sales_invoice_id }}</td><td>{{ $row->payment_method }}</td><td class="text-right">{{ number_format($row->amount, 4) }}</td><td>{{ ucfirst($row->status) }}</td><td>@if($row->status!='confirmed')<form method="post" action="{{ route('distribution-new.collections.confirm',$row->id) }}">@csrf<button class="btn btn-success btn-xs">Confirm</button></form>@endif</td></tr>@endforeach
</tbody></table>{{ $collections->links() }}</div>
</div>
@endsection
