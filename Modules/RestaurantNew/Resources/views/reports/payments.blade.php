@extends('restaurantnew::layouts.app')
@section('content')
@include('restaurantnew::reports.partials.toolbar', ['title' => 'Payments'])
<table class="table table-bordered restaurantnew-report-table"><thead><tr><th>Method</th><th>Payments</th><th>Total</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ ucfirst($row->method) }}</td><td>{{ $row->payments }}</td><td>{{ number_format($row->total, 2) }}</td></tr>@endforeach</tbody></table>
@endsection
