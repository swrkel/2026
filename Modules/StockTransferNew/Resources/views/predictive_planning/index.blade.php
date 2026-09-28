@extends('layouts.app')
@section('title', __('stocktransfernew::messages.predictive_planning'))
@section('content')
<section class="content-header"><h1>{{ __('stocktransfernew::messages.predictive_planning') }}</h1></section>
<section class="content stn-043">
    <div class="box box-solid">
        <div class="box-header with-border"><h3 class="box-title">{{ __('stocktransfernew::messages.forecast_generator') }}</h3></div>
        <form method="POST" action="{{ route('stocktransfernew.predictive-planning.generate') }}" class="box-body stn-grid-form">
            @csrf
            <input name="business_id" class="form-control" placeholder="Business ID" required>
            <input name="from_location_id" class="form-control" placeholder="From Location ID">
            <input name="to_location_id" class="form-control" placeholder="To Location ID" required>
            <input name="from_store_id" class="form-control" placeholder="From Store ID">
            <input name="to_store_id" class="form-control" placeholder="To Store ID" required>
            <input name="forecast_days" type="number" value="30" class="form-control" required>
            <input name="safety_stock_days" type="number" value="7" class="form-control">
            <button class="btn btn-primary">Generate Plan</button>
        </form>
    </div>
    <div class="row stn-kpis">
        @foreach($kpis as $label => $value)
            <div class="col-md-3"><div class="stn-kpi"><span>{{ ucwords(str_replace('_',' ', $label)) }}</span><strong>{{ $value }}</strong></div></div>
        @endforeach
    </div>
    <div class="box box-solid">
        <div class="box-header with-border"><h3 class="box-title">Plans</h3><a href="{{ route('stocktransfernew.predictive-planning.export') }}" class="btn btn-default btn-sm pull-right">CSV</a></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>ID</th><th>Business</th><th>To Location</th><th>To Store</th><th>Forecast</th><th>Status</th><th>Generated</th><th>Action</th></tr></thead>
                <tbody>@foreach($plans as $plan)<tr><td>{{ $plan->id }}</td><td>{{ $plan->business_id }}</td><td>{{ $plan->to_location_id }}</td><td>{{ $plan->to_store_id }}</td><td>{{ $plan->forecast_days }}</td><td>{{ $plan->status }}</td><td>{{ $plan->generated_at }}</td><td><a href="{{ route('stocktransfernew.predictive-planning.show', $plan->id) }}" class="btn btn-xs btn-info">View</a></td></tr>@endforeach</tbody>
            </table>
            {{ $plans->links() }}
        </div>
    </div>
</section>
@endsection
