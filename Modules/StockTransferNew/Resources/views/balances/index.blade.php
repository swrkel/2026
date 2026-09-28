@extends('stocktransfernew::layouts.app')
@section('content')
@include('stocktransfernew::partials.header',['title'=>'Stock Transfer Balances','subtitle'=>'Standalone stock balance mirror maintained by this module'])
<div class="stn-card">
 @include('stocktransfernew::partials.list_toolbar')
 <table class="stn-table"><thead><tr><th>Location</th><th>Store</th><th>Product</th><th>Variation</th><th>On Hand</th><th>In Transit</th><th>Last Cost</th><th>Last Movement</th></tr></thead><tbody>
 @forelse($balances as $balance)<tr><td>{{ $balance->business_location_id ?: '-' }}</td><td>{{ $balance->store_id ?: '-' }}</td><td>{{ $balance->product_id }}</td><td>{{ $balance->variation_id ?: '-' }}</td><td>{{ number_format($balance->qty_on_hand,4) }}</td><td>{{ number_format($balance->qty_in_transit,4) }}</td><td>{{ number_format($balance->last_unit_cost,4) }}</td><td>{{ $balance->last_movement_at }}</td></tr>@empty<tr><td colspan="8">No module balance movements yet.</td></tr>@endforelse
 </tbody></table>{{ $balances->links() }}
</div>
@endsection
