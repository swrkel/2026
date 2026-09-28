@extends('bankingmicrofinance::layouts.app')
@section('page-title','Collection Receipt')
@section('module-content')
<div class="box"><div class="box-body"><h3>Receipt: {{ $collection->receipt_no }}</h3><p>Date: {{ $collection->collection_date }}</p><p>Total Paid: {{ number_format($collection->total_paid,4) }}</p><p>Method: {{ $collection->payment_method }}</p><button onclick="window.print()" class="btn btn-primary">Print</button></div></div>
@endsection
