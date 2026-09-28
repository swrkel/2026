@extends('airlineticketingnew::layouts.app')
@section('atn-title','Ancillary Services')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table">
<thead><tr><th>Service Code</th><th>Name</th><th>Service Type</th><th>Currency Code</th><th>Sale Amount</th><th>Is Active</th></tr></thead>
<tbody>
@forelse($records as $record)
<tr><td>{{ $record->service_code }}</td><td>{{ $record->name }}</td><td>{{ $record->service_type }}</td><td>{{ $record->currency_code }}</td><td>{{ $record->sale_amount }}</td><td>{{ $record->is_active }}</td></tr>
@empty
<tr><td colspan="6" class="text-center">No records</td></tr>
@endforelse
</tbody></table></div>{{ $records->links() }}</div>
@endsection
