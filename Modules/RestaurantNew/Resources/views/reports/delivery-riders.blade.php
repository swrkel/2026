@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.rider_performance_report'))
@section('content')
<div class="rn-page">
    <div class="rn-page-header"><h3>{{ __('restaurantnew::lang.rider_performance_report') }}</h3></div>
    <div class="rn-table-card">
        <table class="table table-bordered rn-datatable"><thead><tr><th>{{ __('restaurantnew::lang.rider') }}</th><th>{{ __('restaurantnew::lang.deliveries') }}</th><th>{{ __('restaurantnew::lang.delivered') }}</th><th>{{ __('restaurantnew::lang.cancelled') }}</th><th>{{ __('restaurantnew::lang.collection') }}</th></tr></thead><tbody>
        @foreach($rows as $row)<tr><td>{{ $row->delivery_rider_id }}</td><td>{{ $row->total_deliveries }}</td><td>{{ $row->delivered_count }}</td><td>{{ $row->cancelled_count }}</td><td>{{ number_format($row->collected_total,4) }}</td></tr>@endforeach
        </tbody></table>
    </div>
</div>
@endsection
