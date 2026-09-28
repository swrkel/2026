@extends('restaurantnew::layouts.app')
@section('rest_title','Ingredient Stock')
@section('rest_subtitle','Independent restaurant ingredient balances and consumption history.')
@section('rest_content')
<div class="rest-grid-3">
<section class="rest-card">
    <h3>Stock Adjustment</h3>
    <form method="post" action="{{ route('restaurant-new.stock.adjust') }}">
        @csrf
        <label>Business Location<select name="location_id"><option value="">All / Default</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected((string)$currentLocationId===(string)$location->id)>{{ $location->name }}</option>@endforeach</select></label>
        <label>Ingredient<select name="ingredient_id" required><option value="">Select</option>@foreach($ingredients as $row)<option value="{{ $row->id }}">{{ $row->ingredient_code }} · {{ $row->name }} ({{ $row->unit }})</option>@endforeach</select></label>
        <label>Quantity (+ / -)<input type="number" step="0.0001" name="quantity" required></label>
        <label>Reason<textarea name="reason" required></textarea></label>
        <button class="rest-btn rest-btn-primary">Post Adjustment</button>
    </form>
</section>
<section class="rest-card rest-span-2">
    <div class="rest-card-head"><h3>Current Balances</h3></div>
    <div class="table-responsive"><table class="rest-table"><thead><tr><th>Location</th><th>Ingredient</th><th>Quantity</th><th>Average Cost</th><th>Value</th></tr></thead><tbody>
    @forelse($balances as $row)<tr><td>{{ $row->location?->name ?: 'All / Default' }}</td><td><strong>{{ $row->ingredient?->ingredient_code }}</strong><small>{{ $row->ingredient?->name }}</small></td><td class="text-right">{{ number_format((float)$row->quantity,4) }}</td><td class="text-right">{{ number_format((float)$row->average_cost,4) }}</td><td class="text-right">{{ number_format((float)$row->quantity*(float)$row->average_cost,4) }}</td></tr>@empty<tr><td colspan="5" class="rest-empty">No ingredient balances yet.</td></tr>@endforelse
    </tbody></table></div>{{ $balances->links() }}
</section>
</div>
<section class="rest-card">
    <div class="rest-card-head"><h3>Latest Stock Movements</h3></div>
    <div class="table-responsive"><table class="rest-table"><thead><tr><th>Date</th><th>Location</th><th>Ingredient</th><th>Type</th><th>Reference</th><th>Quantity</th><th>Value</th><th>Note</th></tr></thead><tbody>
    @foreach($movements as $row)<tr><td>{{ $row->created_at?->format('d M H:i') }}</td><td>{{ $row->location?->name ?: 'All / Default' }}</td><td>{{ $row->ingredient?->name ?: '#'.$row->ingredient_id }}</td><td>{{ ucwords(str_replace('_',' ',$row->movement_type)) }}</td><td>{{ $row->reference_no }}</td><td class="text-right">{{ number_format((float)$row->quantity,4) }}</td><td class="text-right">{{ number_format((float)$row->value,4) }}</td><td>{{ $row->notes }}</td></tr>@endforeach
    </tbody></table></div>
</section>
@endsection
