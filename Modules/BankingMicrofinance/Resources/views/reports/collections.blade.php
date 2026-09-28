@extends('bankingmicrofinance::layouts.app')
@section('page-title','Microfinance Collections Report')
@section('module-content')
<div class="box"><div class="box-body table-responsive">
@if(isset($loans))<table class="table table-bordered"><tr><th>Loan No</th><th>Principal</th><th>Interest</th><th>Total</th><th>Status</th></tr>@foreach($loans as $loan)<tr><td>{{ $loan->loan_no }}</td><td>{{ number_format($loan->principal_amount,4) }}</td><td>{{ number_format($loan->interest_amount,4) }}</td><td>{{ number_format($loan->total_payable,4) }}</td><td>{{ $loan->status }}</td></tr>@endforeach</table>{{ $loans->links() }}@endif
@if(isset($installments))<table class="table table-bordered"><tr><th>Loan</th><th>No</th><th>Due Date</th><th>Total Due</th><th>Paid</th><th>Status</th></tr>@foreach($installments as $row)<tr><td>{{ $row->loan_id }}</td><td>{{ $row->installment_no }}</td><td>{{ $row->due_date }}</td><td>{{ number_format($row->total_due,4) }}</td><td>{{ number_format($row->paid_amount,4) }}</td><td>{{ $row->status }}</td></tr>@endforeach</table>{{ $installments->links() }}@endif
@if(isset($collections))<table class="table table-bordered"><tr><th>Receipt</th><th>Date</th><th>Loan</th><th>Total</th><th>Method</th></tr>@foreach($collections as $collection)<tr><td>{{ $collection->receipt_no }}</td><td>{{ $collection->collection_date }}</td><td>{{ $collection->loan_id }}</td><td>{{ number_format($collection->total_paid,4) }}</td><td>{{ $collection->payment_method }}</td></tr>@endforeach</table>{{ $collections->links() }}@endif
</div></div>
@endsection
