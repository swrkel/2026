@extends('layouts.app')
@section('title','Loan Disbursements')
@section('content')
<section class="content-header"><h1>Loan Disbursements <small>Approved loans ready for payment</small></h1></section>
<section class="content">
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Ready For Disbursement</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Application No</th><th>Customer</th><th>Product</th><th class="text-right">Approved Amount</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($readyApplications as $application)<tr><td>{{ $application->application_no ?? ('APP-'.$application->id) }}</td><td>{{ optional($application->customer)->first_name }} {{ optional($application->customer)->last_name }}</td><td>{{ optional($application->loanProduct)->name ?? '-' }}</td><td class="text-right">{{ number_format(($application->approved_amount ?? $application->principal_amount ?? $application->requested_amount ?? 0), 2) }}</td><td><span class="label label-success">{{ ucwords(str_replace('_',' ',$application->status)) }}</span></td><td><a class="btn btn-xs btn-primary" href="{{ route('loan.disbursements.create',$application->id) }}"><i class="fa fa-money"></i> Disburse</a> <a class="btn btn-xs btn-default" href="{{ route('loan.applications.show',$application->id) }}">View Application</a></td></tr>@empty<tr><td colspan="6" class="text-center text-muted">No approved applications waiting for disbursement.</td></tr>@endforelse
</tbody></table>{{ $readyApplications->links() }}</div></div>
<div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Disbursement History</h3></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Application No</th><th>Loan No</th><th>Customer</th><th class="text-right">Amount</th><th>Method</th><th>Reference</th><th>Action</th></tr></thead><tbody>
@forelse($disbursements as $disbursement)<tr><td>{{ $disbursement->disbursement_date }}</td><td>{{ optional($disbursement->application)->application_no ?? '-' }}</td><td>{{ optional($disbursement->loan)->loan_no ?? '-' }}</td><td>{{ optional(optional($disbursement->application)->customer)->first_name }} {{ optional(optional($disbursement->application)->customer)->last_name }}</td><td class="text-right">{{ number_format($disbursement->disbursement_amount,2) }}</td><td>{{ ucwords(str_replace('_',' ',$disbursement->payment_method)) }}</td><td>{{ $disbursement->reference_no ?? '-' }}</td><td><a class="btn btn-xs btn-default" href="{{ route('loan.disbursements.show',$disbursement->id) }}">View</a></td></tr>@empty<tr><td colspan="8" class="text-center text-muted">No disbursements found.</td></tr>@endforelse
</tbody></table>@if(method_exists($disbursements,'links')){{ $disbursements->links() }}@endif</div></div></section>
@endsection
