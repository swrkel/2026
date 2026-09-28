@extends('bankingmicrofinance::layouts.app')
@section('content')
<div class="container-fluid bkg-mfi-page"><h3>{{ $title ?? 'Banking Microfinance' }}</h3>@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<table class="table table-bordered"><tr><th>Member ID</th><th>Status</th><th>Savings</th><th>Loan Collection</th><th>Remarks</th></tr>@foreach($rows as $row)<tr><td>{{ $row->member_id }}</td><td>{{ $row->attendance_status }}</td><td>{{ number_format($row->savings_collected,4) }}</td><td>{{ number_format($row->loan_collected,4) }}</td><td>{{ $row->remarks }}</td></tr>@endforeach</table>
</div>@endsection
