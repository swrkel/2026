@extends('restaurantnew::layouts.app')
@section('content')
@include('restaurantnew::reports.partials.toolbar', ['title' => 'Waiter Sales'])
<table class="table table-bordered restaurantnew-report-table"><thead><tr><th>Staff</th><th>Bills</th><th>Total</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->staff_name }}</td><td>{{ $row->bills }}</td><td>{{ number_format($row->total, 2) }}</td></tr>@endforeach</tbody></table>
@endsection
