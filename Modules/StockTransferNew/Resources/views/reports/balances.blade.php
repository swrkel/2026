@extends('stocktransfernew::layouts.app')
@section('content')
@include('stocktransfernew::partials.header',['title'=>'Balance Report','subtitle'=>'On-hand and in-transit quantity by location/store'])
<div class="stn-card"><table class="stn-table"><thead><tr><th>Location</th><th>Store</th><th>Product</th><th>On Hand</th><th>In Transit</th><th>Value</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->business_location_id }}</td><td>{{ $row->store_id }}</td><td>{{ $row->product_id }}</td><td>{{ number_format($row->qty_on_hand,4) }}</td><td>{{ number_format($row->qty_in_transit,4) }}</td><td>{{ number_format(($row->qty_on_hand+$row->qty_in_transit)*$row->last_unit_cost,4) }}</td></tr>@endforeach</tbody></table>{{ $rows->links() }}</div>
@endsection
