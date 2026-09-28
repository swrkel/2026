@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::messages.user_access'))
@section('content')
<div class="rn-page">
    <div class="rn-toolbar"><h3>{{ __('restaurantnew::messages.user_access') }}</h3></div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('restaurantnew.admin.user-access.save') }}" class="rn-card">
        @csrf
        <div class="row">
            <div class="col-md-2"><label>Business ID</label><input name="business_id" class="form-control" required></div>
            <div class="col-md-2"><label>Location ID</label><input name="location_id" class="form-control"></div>
            <div class="col-md-2"><label>User ID</label><input name="user_id" class="form-control" required></div>
            <div class="col-md-3"><label>Access Area</label><select name="access_area" class="form-control">@foreach($areas as $area)<option value="{{ $area }}">{{ $area }}</option>@endforeach</select></div>
            <div class="col-md-2"><label>Status</label><select name="is_allowed" class="form-control"><option value="1">Allowed</option><option value="0">Blocked</option></select></div>
            <div class="col-md-1"><label>&nbsp;</label><button class="btn btn-primary rn-btn form-control">Save</button></div>
        </div>
    </form>
</div>
@endsection
