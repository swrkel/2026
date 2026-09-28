@extends('restaurantnew::layouts.app')

@section('title', __('restaurantnew::multi_branch.branch_comparison'))

@section('content')
<div class="rn-page-header"><h1>{{ __('restaurantnew::multi_branch.branch_comparison') }}</h1></div>
<div class="rn-table-wrap">
    <table class="table table-bordered table-striped rn-datatable">
        <thead><tr><th>{{ __('restaurantnew::multi_branch.branch') }}</th><th>{{ __('restaurantnew::multi_branch.sales') }}</th><th>{{ __('restaurantnew::multi_branch.food_cost') }}</th><th>{{ __('restaurantnew::multi_branch.gross_profit') }}</th><th>{{ __('restaurantnew::multi_branch.wastage') }}</th></tr></thead>
        <tbody>
            @foreach(($summary ?? []) as $row)
                <tr><td>{{ $row->business_location_id }}</td><td>{{ number_format($row->sales_total, 4) }}</td><td>{{ number_format($row->food_cost_total, 4) }}</td><td>{{ number_format($row->gross_profit_total, 4) }}</td><td>{{ number_format($row->wastage_total, 4) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
