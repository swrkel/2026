@extends('RiceMill::layout')
@section('rcm-title','Paddy Receiving')
@section('rcm-actions')<a class="rcm-btn" href="{{ route('rice-mill.receipts.create') }}">+ Receive Paddy</a>@endsection
@section('rcm-content')
    @include('RiceMill::receipts.partials.receipt-list',['rows'=>$rows])
@endsection
