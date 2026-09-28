@extends('autoservice::layouts.master')
@section('title','Profitability Report')
@section('autoservice_content')
<div class="box"><div class="box-body"><table class="table table-bordered"><tr><th>Job No</th><th>Total</th><th>Paid</th><th>Balance</th></tr>@foreach($rows as $r)<tr><td>{{ $r->job_no }}</td><td>{{ number_format($r->total_amount,2) }}</td><td>{{ number_format($r->paid_amount,2) }}</td><td>{{ number_format($r->balance_amount,2) }}</td></tr>@endforeach</table>{{ $rows->links() }}</div></div>
@endsection
