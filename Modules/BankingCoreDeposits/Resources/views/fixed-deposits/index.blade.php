@extends('bankingcoredeposits::layouts.app')
@section('page-title','Fixed Deposits')
@section('module-content')<a class="btn btn-primary" href="{{ route('banking.core-deposits.fixed-deposits.create') }}">Open FD</a><table class="table table-bordered"><tr><th>FD No</th><th>Principal</th><th>Rate</th><th>Maturity</th><th>Status</th></tr>@foreach($fds as $fd)<tr><td>{{ $fd->fd_no }}</td><td>{{ number_format($fd->principal_amount,4) }}</td><td>{{ $fd->interest_rate }}</td><td>{{ $fd->maturity_date }}</td><td>{{ $fd->status }}</td></tr>@endforeach</table>{{ $fds->links() }}@endsection
