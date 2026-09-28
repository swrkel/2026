@extends('bankingmicrofinance::layouts.app')
@section('page-title','Loan Details')
@section('module-content')
<div class="box"><div class="box-body"><h4>{{ $loan->loan_no }}</h4><p>Principal: {{ number_format($loan->principal_amount,4) }} | Interest: {{ number_format($loan->interest_amount,4) }} | Total: {{ number_format($loan->total_payable,4) }} | Status: {{ ucfirst($loan->status) }}</p>
@if(in_array($loan->status,['created','verified']))<form method="post" action="{{ route('banking.microfinance.loans.approve',$loan) }}">@csrf<textarea name="approval_note" class="form-control" placeholder="Approval note"></textarea><button class="btn btn-success mt-2">Approve</button></form>@endif
@if($loan->status==='approved')<form method="post" action="{{ route('banking.microfinance.loans.disburse',$loan) }}">@csrf<button class="btn btn-warning">Disburse</button></form>@endif
</div></div>
<div class="box"><div class="box-header"><h3 class="box-title">Repayment Schedule</h3></div><div class="box-body table-responsive"><table class="table table-bordered"><tr><th>No</th><th>Due Date</th><th>Principal</th><th>Interest</th><th>Total</th><th>Paid</th><th>Status</th></tr>@foreach($installments as $row)<tr><td>{{ $row->installment_no }}</td><td>{{ $row->due_date }}</td><td>{{ number_format($row->principal_due,4) }}</td><td>{{ number_format($row->interest_due,4) }}</td><td>{{ number_format($row->total_due,4) }}</td><td>{{ number_format($row->paid_amount,4) }}</td><td>{{ ucfirst($row->status) }}</td></tr>@endforeach</table></div></div>
@endsection
