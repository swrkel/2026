@extends('bankingcheque::layout')
@section('banking_cheque_content')<table class="table table-bordered"><tr><th>Account</th><th>Leaf</th><th>Amount</th><th>Reason</th></tr>@foreach($items as $item)<tr><td>{{ $item->account_no }}</td><td>{{ $item->leaf_no }}</td><td>{{ number_format($item->amount,4) }}</td><td>{{ $item->return_reason }}</td></tr>@endforeach</table>@endsection
