@extends('bankingcheque::layout')
@section('banking_cheque_content')<table class="table table-bordered"><tr><th>Account</th><th>Leaf</th><th>Status</th><th>Amount</th></tr>@foreach($leaves as $leaf)<tr><td>{{ $leaf->account_no }}</td><td>{{ $leaf->leaf_no }}</td><td>{{ $leaf->status }}</td><td>{{ number_format($leaf->amount,4) }}</td></tr>@endforeach</table>{{ $leaves->links() }}@endsection
