@extends('restaurantnew::layouts.app')
@section('content')
@include('restaurantnew::reports.partials.toolbar', ['title' => 'Item Sales'])
<table class="table table-bordered restaurantnew-report-table"><thead><tr><th>Item</th><th>Qty</th><th>Total</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->item_name }}</td><td>{{ number_format($row->qty, 3) }}</td><td>{{ number_format($row->total, 2) }}</td></tr>@endforeach</tbody></table>
@endsection
