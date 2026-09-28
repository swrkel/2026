@extends('beautysaloons::layout')
@section('content')
<div class="bs-portal-login">
    <h3>Beauty Saloons Customer Login</h3>
    <form method="POST" action="{{ route('beautysaloons.portal.login.submit') }}">
        @csrf
        <div class="form-group">
            <label>Mobile or Email</label>
            <input type="text" name="login" class="form-control" value="{{ old('login') }}" required>
            @error('login')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">Login</button>
    </form>
</div>
@endsection
