@extends('distributionnew::layouts.app')
@section('content')
<div class="disnew-page">

@include('distributionnew::partials.erp-standard-styles')<div class="disnew-card"><div class="disnew-card-header"><h3>Vehicle Stock Balance</h3></div><table class="table table-bordered"><thead><tr><th>Vehicle</th><th>Product</th><th>Qty</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->vehicle_id }}</td><td>{{ $row->product_id }}</td><td class="text-right">{{ number_format($row->qty,4) }}</td></tr>@endforeach</tbody></table></div>
</div>
@endsection
