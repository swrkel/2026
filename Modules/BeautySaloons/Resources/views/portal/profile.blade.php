@extends('beautysaloons::portal.layout')
@section('portal_title', 'My Profile')
@section('portal_content')
<form method="POST" action="{{ route('beautysaloons.portal.profile.update') }}">
    @csrf
    <div class="row">
        <div class="col-md-6"><label>Name</label><input name="name" class="form-control" value="{{ $customer->name ?? '' }}"></div>
        <div class="col-md-6"><label>Mobile</label><input name="mobile" class="form-control" value="{{ $customer->mobile ?? '' }}"></div>
        <div class="col-md-6"><label>Email</label><input name="email" class="form-control" value="{{ $customer->email ?? '' }}"></div>
        <div class="col-md-12"><label>Address</label><textarea name="address" class="form-control">{{ $customer->address ?? '' }}</textarea></div>
    </div>
    <button class="btn btn-primary mt-3">Update Profile</button>
</form>
@endsection
