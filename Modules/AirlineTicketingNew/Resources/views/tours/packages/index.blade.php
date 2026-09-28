@extends('airlineticketingnew::layouts.app')
@section('atn-title','Tour Packages')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>Package Code</th><th>Name</th><th>Package Type</th><th>Destination City</th><th>Duration Days</th><th>Sale Amount</th><th>Status</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->package_code }}</td><td>{{ $record->name }}</td><td>{{ $record->package_type }}</td><td>{{ $record->destination_city }}</td><td>{{ $record->duration_days }}</td><td>{{ $record->sale_amount }}</td><td>{{ $record->status }}</td></tr>@empty<tr><td colspan="7" class="text-center">No records</td></tr>@endforelse
</tbody></table></div>{{ $records->links() }}</div>
@endsection
