@extends('airlineticketingnew::layouts.app')
@section('atn-title','Corporate Agreements')
@section('atn-content')
@include('airlineticketingnew::partials.full_navigation')
<div class="atn-panel"><div class="table-responsive"><table class="table table-bordered atn-table"><thead><tr><th>Agreement No</th><th>Corporate Customer Id</th><th>Effective From</th><th>Effective To</th><th>Credit Limit</th><th>Credit Days</th><th>Status</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ $record->agreement_no }}</td><td>{{ $record->corporate_customer_id }}</td><td>{{ $record->effective_from }}</td><td>{{ $record->effective_to }}</td><td>{{ $record->credit_limit }}</td><td>{{ $record->credit_days }}</td><td>{{ $record->status }}</td></tr>@empty<tr><td colspan="7" class="text-center">No records</td></tr>@endforelse
</tbody></table></div>{{ $records->links() }}</div>
@endsection
