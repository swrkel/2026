@extends('restaurantnew::layouts.app')
@section('content')
@include('restaurantnew::reports.partials.toolbar', ['title' => 'Cancelled / Void Bills'])
<table class="table table-bordered restaurantnew-report-table"><thead><tr><th>Order No</th><th>Status</th><th>Total</th><th>Reason</th><th>Date</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->order_no }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ number_format($row->total_amount, 2) }}</td><td>{{ $row->void_reason ?: $row->cancel_reason }}</td><td>{{ $row->created_at }}</td></tr>@endforeach</tbody></table>
@endsection
