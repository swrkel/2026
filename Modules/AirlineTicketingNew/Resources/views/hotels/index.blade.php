@extends('airlineticketingnew::layouts.app')
@section('atn-title','Hotels')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>Hotel Code</th><th>Name</th><th>City</th><th>Star Rating</th><th>Supplier Id</th><th>Is Active</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->hotel_code }}</td><td>{{ $record->name }}</td><td>{{ $record->city }}</td><td>{{ $record->star_rating }}</td><td>{{ $record->supplier_id }}</td><td>{{ $record->is_active }}</td></tr>@empty<tr><td colspan="6" class="text-center">No records</td></tr>@endforelse
</tbody></table></div>{{ $records->links() }}</div>
@endsection
