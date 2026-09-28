@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.wastage'))
@section('content')
<div class="restnew-page"><div class="restnew-header"><h1>{{ __('restaurantnew::lang.wastage') }}</h1></div><div class="restnew-card">@include('restaurantnew::partials.toolbar')<table class="table table-bordered restnew-table"><thead><tr><th>{{ __('restaurantnew::lang.date') }}</th><th>{{ __('restaurantnew::lang.ingredient') }}</th><th>{{ __('restaurantnew::lang.quantity') }}</th><th>{{ __('restaurantnew::lang.total_cost') }}</th><th>{{ __('restaurantnew::lang.reason') }}</th></tr></thead><tbody>@foreach($wastages as $wastage)<tr><td>{{ $wastage->wastage_date }}</td><td>{{ $wastage->ingredient_id }}</td><td>{{ number_format($wastage->quantity,4) }}</td><td>{{ number_format($wastage->total_cost,4) }}</td><td>{{ $wastage->reason }}</td></tr>@endforeach</tbody></table>{{ $wastages->links() }}</div></div>
@endsection
