@extends('distributionnew::layouts.app')
@section('content')
@include('distributionnew::partials.erp-standard-styles')
<div class="pos-card">
    <div class="pos-card-header"><h4>@lang('distributionnew::messages.routes')</h4></div>
    <form method="POST" action="{{ route('distributionnew.routes.store') }}" class="row">@csrf
        <div class="col-md-2"><input name="route_code" class="form-control" placeholder="Route Code" required></div>
        <div class="col-md-3"><input name="route_name" class="form-control" placeholder="Route Name" required></div>
        <div class="col-md-3"><select name="disnew_territory_id" class="form-control"><option value="">Select Territory</option>@foreach($territories as $territory)<option value="{{ $territory->id }}">{{ $territory->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><input name="visit_day" class="form-control" placeholder="Visit Day"></div>
        <div class="col-md-2"><button class="btn btn-primary btn-block">Save</button></div>
    </form>
    <hr>
    <table class="table table-bordered table-striped disnew-table"><thead><tr><th>Route Code</th><th>Route Name</th><th>Visit Day</th><th>Status</th></tr></thead><tbody>
    @foreach($routes as $route)<tr><td>{{ $route->route_code }}</td><td>{{ $route->route_name }}</td><td>{{ $route->visit_day }}</td><td>{{ $route->is_active ? 'Active' : 'Inactive' }}</td></tr>@endforeach
    </tbody></table>
</div>
@endsection
