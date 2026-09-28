@extends('bankingmicrofinance::layouts.app')
@section('content')
<div class="container-fluid bkg-mfi-page"><h3>{{ $title ?? 'Banking Microfinance' }}</h3>@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<table class="table table-bordered"><tr><th>Loan</th><th>Overdue Amount</th><th>Installments</th></tr>@foreach($rows as $row)<tr><td>{{ $row->loan_id }}</td><td>{{ number_format($row->overdue_amount,4) }}</td><td>{{ $row->installment_count }}</td></tr>@endforeach</table>{{ $rows->links() }}
</div>@endsection
