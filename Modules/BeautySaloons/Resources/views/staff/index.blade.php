@extends('layouts.app')
@section('title', __('beautysaloons::lang.staff'))
@section('content')
<section class="content-header"><h1>{{ __('beautysaloons::lang.staff') }}</h1></section>
<section class="content"><div class="box"><div class="box-body"><table class="table table-bordered" id="bs_staff_table"><thead><tr><th>Code</th><th>Name</th><th>Mobile</th><th>Designation</th><th>Status</th><th>Action</th></tr></thead></table></div></div></section>
@endsection
