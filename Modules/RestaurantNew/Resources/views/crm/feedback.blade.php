@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::crm.feedback'))
@section('content')
<div class="rn-page"><div class="rn-toolbar"><h3>{{ __('restaurantnew::crm.feedback_cases') }}</h3></div><div class="rn-card"><table class="table table-bordered"><thead><tr><th>Date</th><th>Food</th><th>Service</th><th>Status</th><th>Priority</th><th>Comments</th></tr></thead><tbody>@foreach($cases as $case)<tr><td>{{ $case->created_at }}</td><td>{{ $case->food_rating }}</td><td>{{ $case->service_rating }}</td><td>{{ $case->case_status }}</td><td>{{ $case->priority }}</td><td>{{ $case->customer_comments }}</td></tr>@endforeach</tbody></table>{{ $cases->links() }}</div></div>
@endsection
