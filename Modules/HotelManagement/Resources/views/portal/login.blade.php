@extends('layouts.app')
@section('content')
<section class="content-header"><h1>Hotel Guest Portal Login</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
<form method="POST" action="{{ route('hotel-management.portal.authenticate') }}">@csrf
<div class="form-group"><label>Email / Mobile</label><input name="login" class="form-control"></div>
<div class="form-group"><label>Password</label><input name="password" type="password" class="form-control"></div>
<button class="btn btn-primary">Login</button>
</form>
</div></div></section>
@endsection
