@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.delivery_summary_report'))
@section('content')
<div class="rn-page">
    <div class="rn-page-header"><h3>{{ __('restaurantnew::lang.delivery_summary_report') }}</h3></div>
    <div class="rn-table-card">
        <table class="table table-bordered rn-datatable"><thead><tr><th>{{ __('restaurantnew::lang.status') }}</th><th>{{ __('restaurantnew::lang.orders') }}</th><th>{{ __('restaurantnew::lang.delivery_charge') }}</th><th>{{ __('restaurantnew::lang.cod') }}</th><th>{{ __('restaurantnew::lang.card') }}</th></tr></thead><tbody>
        @foreach($rows as $row)<tr><td>{{ ucfirst($row->delivery_status) }}</td><td>{{ $row->total_orders }}</td><td>{{ number_format($row->total_delivery_charge,4) }}</td><td>{{ number_format($row->total_cod,4) }}</td><td>{{ number_format($row->total_card,4) }}</td></tr>@endforeach
        </tbody></table>
    </div>
</div>
@endsection
