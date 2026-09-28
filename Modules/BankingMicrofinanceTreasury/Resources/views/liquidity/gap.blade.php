@extends('bankingmicrofinancetreasury::layout')
@section('treasury_content')<table class="table table-bordered">@foreach($buckets as $bucket=>$amount)<tr><td>{{ $bucket }}</td><td>{{ number_format($amount,4) }}</td></tr>@endforeach</table>@endsection
