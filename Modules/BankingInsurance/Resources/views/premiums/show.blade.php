@extends('bankinginsurance::layouts.app')
@section('bankinginsurance_content')<div class="box"><div class="box-body">{{ $premium->receipt_no }} - {{ number_format($premium->amount,4) }}</div></div>@endsection
