@extends('distributionnew::layouts.app')
@section('title','Loading Plans')
@section('subtitle','Plan vehicle loading from sales orders before physical loading.')
@section('page_actions')<a class="btn btn-primary" href="{{ route('distributionnew.loading-plans.create') }}">Create Loading Plan</a>@endsection
@section('module_content')
<div class="disnew-card">
    <div class="disnew-toolbar"><input class="form-control" placeholder="Search loading plans"></div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover disnew-table">
            <thead><tr><th>Plan No</th><th>Date</th><th>Vehicle</th><th>Sales Rep</th><th>Status</th><th class="text-right">Action</th></tr></thead>
            <tbody>
            @forelse($plans as $plan)
                <tr>
                    <td>{{ $plan->plan_no }}</td><td>{{ $plan->plan_date }}</td><td>{{ $plan->vehicle_id }}</td><td>{{ $plan->sales_rep_id }}</td>
                    <td><span class="badge badge-info">{{ ucfirst(str_replace('_',' ', $plan->status)) }}</span></td>
                    <td class="text-right"><a class="btn btn-sm btn-primary" href="{{ route('distributionnew.loading-plans.show', $plan->id) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No loading plans found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ method_exists($plans,'links') ? $plans->links() : '' }}
</div>
@endsection
