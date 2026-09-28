@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::production.batch_production'))
@section('content')
<div class="restaurant-new-page">
    <div class="rn-toolbar rn-toolbar--pos-standard">
        <h3>{{ __('restaurantnew::production.batch_production') }}</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped rn-datatable">
            <thead><tr><th>Batch No</th><th>Status</th><th>Input Cost</th><th>Output Cost</th><th>Wastage Cost</th><th>Started</th><th>Completed</th></tr></thead>
            <tbody>
            @foreach($batches as $batch)
                <tr><td>{{ $batch->batch_no }}</td><td>{{ $batch->status }}</td><td>{{ number_format($batch->input_cost, 4) }}</td><td>{{ number_format($batch->output_cost, 4) }}</td><td>{{ number_format($batch->wastage_cost, 4) }}</td><td>{{ $batch->started_at }}</td><td>{{ $batch->completed_at }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $batches->links() }}
</div>
@endsection
