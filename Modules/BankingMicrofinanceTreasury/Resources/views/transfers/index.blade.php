@extends('bankingmicrofinancetreasury::layout')
@section('treasury_content')
<a class="btn btn-primary mb-3" href="{{ route('bkg.mfi.treasury.inter-branch-transfers.create') }}">Add Inter Branch Transfers</a>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>ID</th><th>Reference</th><th>Status</th><th>Amount/Balance</th><th>Action</th></tr></thead><tbody>
@foreach($transfers as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->vault_code ?? $row->transfer_no ?? $row->line_code }}</td><td>{{ $row->status }}</td><td>{{ number_format($row->current_balance ?? $row->amount ?? $row->approved_limit ?? 0,4) }}</td><td><a class="btn btn-sm btn-secondary" href="#">View</a></td></tr>@endforeach
</tbody></table></div>
@endsection
