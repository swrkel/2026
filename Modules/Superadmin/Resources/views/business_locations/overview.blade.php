@extends('layouts.app')

@section('title', 'Business & Location Overview')

@section('content')
<section class="content-header">
    <h1>Business & Location Overview</h1>
    <p class="text-muted">Central Super Admin view of every tenant business and its linked locations.</p>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-map-marker"></i> All business locations</h3>
        </div>
        <div class="box-body">
            <form method="get" action="{{ route('superadmin.business-locations-overview') }}" class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="business_location_overview_search">Search</label>
                        <input id="business_location_overview_search" type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Business, location, code, city or mobile">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="business_location_overview_business">Business</label>
                        {!! Form::select('business_id', $businesses, request('business_id'), ['id' => 'business_location_overview_business', 'class' => 'form-control select2', 'placeholder' => 'All businesses', 'style' => 'width:100%;']) !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="business_location_overview_status">Status</label>
                        <select id="business_location_overview_status" name="status" class="form-control">
                            <option value="">All</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group" style="padding-top:25px;">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Show</button>
                        <a href="{{ route('superadmin.business-locations-overview') }}" class="btn btn-default">Clear</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Business</th>
                            <th>Location</th>
                            <th>Location code</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($locations as $location)
                            <tr>
                                <td><strong>{{ $location->business_name }}</strong><br><small>#{{ $location->business_id }}</small></td>
                                <td>{{ $location->location_name }}</td>
                                <td>{{ $location->location_id ?: '—' }}</td>
                                <td>{{ collect([$location->address_1, $location->address_2, $location->city, $location->state, $location->country])->filter()->implode(', ') ?: '—' }}</td>
                                <td>{{ $location->mobile ?: '—' }}@if($location->email)<br><small>{{ $location->email }}</small>@endif</td>
                                <td>
                                    <span class="label {{ $location->is_active ? 'label-success' : 'label-danger' }}">
                                        {{ $location->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">No matching business locations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="text-center">{{ $locations->links() }}</div>
        </div>
    </div>
</section>
@stop

@section('javascript')
<script>
$(function () {
    if ($.fn.select2) {
        $('#business_location_overview_business').select2();
    }
});
</script>
@stop
