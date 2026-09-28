@extends('layouts.app')
@section('title', 'Predictive Plan #'.$plan->id)
@section('content')
<section class="content-header"><h1>Predictive Plan #{{ $plan->id }}</h1></section>
<section class="content stn-043">
    <div class="box box-solid"><div class="box-body">
        <strong>Status:</strong> {{ $plan->status }} &nbsp; <strong>Forecast Days:</strong> {{ $plan->forecast_days }} &nbsp; <strong>Safety Days:</strong> {{ $plan->safety_stock_days }}
    </div></div>
    <div class="box box-solid"><div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Product</th><th>Variation</th><th>Avg Daily</th><th>Forecast</th><th>Safety</th><th>Suggested</th><th>Approved</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>@foreach($lines as $line)<tr>
                <td>{{ $line->product_id }}</td><td>{{ $line->variation_id }}</td><td>{{ number_format($line->average_daily_qty, 4) }}</td><td>{{ number_format($line->forecast_qty, 4) }}</td><td>{{ number_format($line->safety_stock_qty, 4) }}</td><td>{{ number_format($line->suggested_qty, 4) }}</td><td>{{ number_format($line->approved_qty, 4) }}</td><td>{{ $line->status }}</td>
                <td><form method="POST" action="{{ route('stocktransfernew.predictive-planning.approve-line', $line->id) }}" class="stn-inline-form">@csrf<input name="approved_qty" value="{{ $line->suggested_qty }}" class="form-control input-sm"><button class="btn btn-xs btn-success">Approve</button></form><form method="POST" action="{{ route('stocktransfernew.predictive-planning.reject-line', $line->id) }}" class="stn-inline-form">@csrf<input name="remarks" placeholder="Reason" class="form-control input-sm"><button class="btn btn-xs btn-danger">Reject</button></form></td>
            </tr>@endforeach</tbody>
        </table>
        {{ $lines->links() }}
    </div></div>
</section>
@endsection
