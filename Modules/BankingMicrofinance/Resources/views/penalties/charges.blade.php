@extends('bankingmicrofinance::layouts.app')
@section('content')
<div class="container-fluid bkg-mfi-page"><h3>{{ $title ?? 'Banking Microfinance' }}</h3>@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<table class="table table-bordered"><tr><th>Date</th><th>Loan</th><th>Installment</th><th>Overdue</th><th>Days</th><th>Penalty</th><th>Waived</th><th>Status</th></tr>@foreach($items as $item)<tr><td>{{ $item->penalty_date }}</td><td>{{ $item->loan_id }}</td><td>{{ $item->installment_id }}</td><td>{{ number_format($item->overdue_amount,4) }}</td><td>{{ $item->overdue_days }}</td><td>{{ number_format($item->penalty_amount,4) }}</td><td>{{ number_format($item->waived_amount,4) }}</td><td>{{ $item->status }}</td></tr>@endforeach</table>{{ $items->links() }}
</div>@endsection
