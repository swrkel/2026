@extends('layouts.app')
@section('title', 'My Health Doctor')
@section('content')
<section class="content-header"><h1>Doctor Profile</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body"><dl class="dl-horizontal"><dt>Name</dt><dd>{{ $doctor->name ?? '-' }}</dd><dt>Specialty</dt><dd>{{ $doctor->specialty ?? '-' }}</dd><dt>Mobile</dt><dd>{{ $doctor->mobile ?? '-' }}</dd><dt>Email</dt><dd>{{ $doctor->email ?? '-' }}</dd></dl></div></div></section>
@endsection
