@extends('layouts.app')
@section('title', 'Pharmacy Stock')
@section('content')
<section class="content-header"><h1>Pharmacy Stock Ledger</h1></section>
<section class="content">
@if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
<a href="{{ route('myhealth.pharmacy.stock.create') }}" class="btn btn-success mb-3">Add Stock</a> <a href="{{ route('myhealth.pharmacy.dashboard') }}" class="btn btn-default mb-3">Dashboard</a>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Medicine</th><th>Batch</th><th>Type</th><th>Reference</th><th>In</th><th>Out</th><th>Balance</th><th>Notes</th></tr></thead><tbody>
@foreach($ledger as $row)<tr><td>{{ $row->transaction_date }}</td><td>{{ optional($row->medicine)->medicine_name }}</td><td>{{ optional($row->batch)->batch_no }}</td><td>{{ ucfirst(str_replace('_',' ', $row->transaction_type)) }}</td><td>{{ $row->reference_no }}</td><td>{{ number_format($row->qty_in, 4) }}</td><td>{{ number_format($row->qty_out, 4) }}</td><td>{{ number_format($row->balance_qty, 4) }}</td><td>{{ $row->notes }}</td></tr>@endforeach
</tbody></table></div>{{ $ledger->links() }}
</section>
@endsection
