@extends('restaurantnew::layouts.app')
@section('content')
@include('restaurantnew::reports.partials.toolbar', ['title' => 'Tax & Service Charge'])
<table class="table table-bordered restaurantnew-report-table"><thead><tr><th>Date</th><th>Tax</th><th>Service Charge</th><th>Total Sales</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->report_date }}</td><td>{{ number_format($row->tax, 2) }}</td><td>{{ number_format($row->service_charge, 2) }}</td><td>{{ number_format($row->total, 2) }}</td></tr>@endforeach</tbody></table>
@endsection
