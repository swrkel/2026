@extends('airlineticketingnew::layouts.app')
@section('atn-title','Service Bundles')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Bundle Code</th><th>Name</th><th>Currency Code</th><th>Bundle Price</th><th>Is Active</th></tr></thead>
<tbody>
@forelse($records as $record)
<tr><td>{{ $record->bundle_code }}</td><td>{{ $record->name }}</td><td>{{ $record->currency_code }}</td><td>{{ $record->bundle_price }}</td><td>{{ $record->is_active }}</td></tr>
@empty
<tr><td colspan="5" class="text-center">No records</td></tr>
@endforelse
</tbody></table></div>{{ $records->links() }}</div>
@endsection
