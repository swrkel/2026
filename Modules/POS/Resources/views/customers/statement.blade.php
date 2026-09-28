@extends('pos::layouts.app')
@section('pos_content')
<div class="card"><div class="card-body" style="display:flex;justify-content:space-between;gap:10px;"><div><h2 style="margin:0;">Customer Statement</h2><strong>{{ $customer->name }}</strong><br>{{ $customer->customer_code }} | {{ $customer->mobile }}</div><div class="text-right"><button class="btn btn-primary" onclick="window.print()">Print</button><br><br><strong>Balance: {{ number_format($customer->balance_amount,2) }}</strong></div></div></div>
@include('pos::customers._ledger_table')
@endsection
