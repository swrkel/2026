@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.current_stock'))
@section('content')
<div class="restnew-page"><div class="restnew-header"><h1>{{ __('restaurantnew::lang.current_stock') }}</h1></div><div class="restnew-card">@include('restaurantnew::partials.toolbar')<table class="table table-bordered restnew-table"><thead><tr><th>{{ __('restaurantnew::lang.ingredient') }}</th><th>{{ __('restaurantnew::lang.quantity') }}</th><th>{{ __('restaurantnew::lang.average_cost') }}</th><th>{{ __('restaurantnew::lang.stock_value') }}</th></tr></thead><tbody>@foreach($stocks as $stock)<tr><td>{{ optional($stock->ingredient)->name }}</td><td>{{ number_format($stock->quantity,4) }}</td><td>{{ number_format($stock->average_cost,4) }}</td><td>{{ number_format($stock->stock_value,4) }}</td></tr>@endforeach</tbody></table>{{ $stocks->links() }}</div></div>
@endsection
