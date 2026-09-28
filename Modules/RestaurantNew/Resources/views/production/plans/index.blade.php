@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::production.production_plans'))
@section('content')
<div class="restaurant-new-page">
    <div class="rn-toolbar rn-toolbar--pos-standard">
        <h3>{{ __('restaurantnew::production.production_plans') }}</h3>
        <button class="btn btn-primary" data-toggle="modal" data-target="#rnPlanModal">{{ __('restaurantnew::production.new_plan') }}</button>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped rn-datatable">
            <thead><tr><th>No</th><th>Date</th><th>Type</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @foreach($plans as $plan)
                <tr>
                    <td>{{ $plan->plan_no }}</td><td>{{ optional($plan->plan_date)->format('Y-m-d') }}</td><td>{{ $plan->production_type }}</td><td>{{ $plan->status }}</td>
                    <td>@if($plan->status === 'draft')<form method="post" action="{{ route('restaurantnew.production.plans.approve', $plan) }}">@csrf<button class="btn btn-xs btn-success">Approve</button></form>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $plans->links() }}
</div>
@endsection
