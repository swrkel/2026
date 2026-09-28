@extends('bankingmicrofinance::layouts.app')
@section('content')
<div class="container-fluid bkg-mfi-page"><h3>{{ $title ?? 'Banking Microfinance' }}</h3>@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<table class="table table-bordered"><tr><th>Status</th><th>Loans</th><th>Principal</th><th>Balance</th></tr>@foreach($rows as $row)<tr><td>{{ $row->status }}</td><td>{{ $row->loans }}</td><td>{{ number_format($row->principal,4) }}</td><td>{{ number_format($row->balance,4) }}</td></tr>@endforeach</table>
</div>@endsection
