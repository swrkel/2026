@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="pn-card"><div class="pn-card-header"><strong>Stock Valuation Report</strong></div><div class="pn-card-body"><table class="table pn-table"><thead><tr><th>Product</th><th>SKU</th><th>Location</th><th class="text-right">Qty</th><th class="text-right">Value</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->name }}</td><td>{{ $row->sku }}</td><td>{{ $row->location_name }}</td><td class="text-right">{{ number_format((float)$row->qty_available,3) }}</td><td class="text-right">{{ number_format((float)$row->stock_value,4) }}</td></tr>@endforeach</tbody></table>{{ $rows->links() }}</div></div>
@endsection
