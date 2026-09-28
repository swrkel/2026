@extends('layouts.app')
@section('title', 'Dispense Details')
@section('content')
<section class="content-header"><h1>Dispense Details - {{ $dispense->dispense_no }}</h1></section>
<section class="content">
@if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
<div class="box box-primary"><div class="box-body">
<p><strong>Member:</strong> {{ optional($dispense->member)->myhealth_code }} - {{ optional($dispense->member)->name }}</p>
<p><strong>Date:</strong> {{ $dispense->dispense_date }} <strong>Status:</strong> {{ ucfirst(str_replace('_',' ', $dispense->status)) }}</p>
<p><strong>Total:</strong> {{ number_format($dispense->total_amount, 4) }}</p>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Medicine</th><th>Batch</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Instructions</th></tr></thead><tbody>
@foreach($dispense->items as $item)<tr><td>{{ $item->medicine_name }}</td><td>{{ optional($item->batch)->batch_no }}</td><td>{{ $item->dosage }}</td><td>{{ $item->frequency }}</td><td>{{ $item->duration }}</td><td>{{ number_format($item->quantity, 4) }}</td><td>{{ number_format($item->unit_price, 4) }}</td><td>{{ number_format($item->line_total, 4) }}</td><td>{{ $item->instructions }}</td></tr>@endforeach
</tbody></table></div>
<a href="{{ route('myhealth.pharmacy.dispensing.index') }}" class="btn btn-default">Back</a>
</div></div>
</section>
@endsection
