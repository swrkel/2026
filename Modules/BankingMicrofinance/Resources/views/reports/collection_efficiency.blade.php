@extends('bankingmicrofinance::layouts.app')
@section('content')
<div class="container-fluid bkg-mfi-page"><h3>{{ $title ?? 'Banking Microfinance' }}</h3>@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<table class="table table-bordered"><tr><th>Date</th><th>Collected</th></tr>@foreach($rows as $row)<tr><td>{{ $row->date }}</td><td>{{ number_format($row->collected,4) }}</td></tr>@endforeach</table>{{ $rows->links() }}
</div>@endsection
