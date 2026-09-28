@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.ingredients'))
@section('content')
<div class="restnew-page">
  <div class="restnew-header"><h1>{{ __('restaurantnew::lang.ingredients') }}</h1><p>{{ __('restaurantnew::lang.ingredients_subtitle') }}</p></div>
  <div class="restnew-card">
    <form method="POST" action="{{ route('restaurantnew.inventory.ingredients.store') }}" class="restnew-grid restnew-grid-4">
      @csrf
      <input name="name" class="form-control" placeholder="{{ __('restaurantnew::lang.name') }}" required>
      <input name="sku" class="form-control" placeholder="{{ __('restaurantnew::lang.sku') }}">
      <input name="unit" class="form-control" placeholder="{{ __('restaurantnew::lang.unit') }}" value="unit" required>
      <input name="purchase_price" class="form-control" placeholder="{{ __('restaurantnew::lang.purchase_price') }}" type="number" step="0.0001">
      <input name="reorder_level" class="form-control" placeholder="{{ __('restaurantnew::lang.reorder_level') }}" type="number" step="0.0001">
      <button class="btn btn-primary restnew-btn">{{ __('restaurantnew::lang.save') }}</button>
    </form>
  </div>
  <div class="restnew-card">
    @include('restaurantnew::partials.toolbar')
    <table class="table table-bordered table-striped restnew-table">
      <thead><tr><th>{{ __('restaurantnew::lang.name') }}</th><th>{{ __('restaurantnew::lang.sku') }}</th><th>{{ __('restaurantnew::lang.unit') }}</th><th>{{ __('restaurantnew::lang.purchase_price') }}</th><th>{{ __('restaurantnew::lang.reorder_level') }}</th></tr></thead>
      <tbody>@forelse($ingredients as $ingredient)<tr><td>{{ $ingredient->name }}</td><td>{{ $ingredient->sku }}</td><td>{{ $ingredient->unit }}</td><td>{{ number_format($ingredient->purchase_price, 4) }}</td><td>{{ number_format($ingredient->reorder_level, 4) }}</td></tr>@empty<tr><td colspan="5" class="text-center">{{ __('restaurantnew::lang.no_records') }}</td></tr>@endforelse</tbody>
    </table>
    {{ $ingredients->links() }}
  </div>
</div>
@endsection
