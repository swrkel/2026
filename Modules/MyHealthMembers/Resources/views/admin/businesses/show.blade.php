@extends('layouts.app')
@section('title', 'My Health Business')
@section('content')
<section class="content-header"><h1>Business Profile</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body"><dl class="dl-horizontal"><dt>Name</dt><dd>{{ $business->name ?? '-' }}</dd><dt>Email</dt><dd>{{ $business->email ?? '-' }}</dd><dt>Mobile</dt><dd>{{ $business->mobile ?? '-' }}</dd></dl><a class="btn btn-primary" href="{{ route('myhealth.admin.permissions.index', ['business_id' => $business->id]) }}">Manage Permissions</a></div></div></section>
@endsection
