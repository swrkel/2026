@extends('layouts.app')
@section('title', 'My Health Permission Matrix')
@section('content')
<section class="content-header"><h1>Permission Matrix</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.admin.permissions.store') }}">@csrf<div class="box box-primary"><div class="box-body">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="form-group"><label>Business</label><select name="business_id" class="form-control" required><option value="">Select Business</option>@foreach($businesses as $business)<option value="{{ $business->id }}" {{ request('business_id') == $business->id ? 'selected' : '' }}>{{ $business->name }}</option>@endforeach</select></div>
<div class="row">@foreach($sections as $section => $permissions)<div class="col-md-6"><div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">{{ ucfirst(str_replace('_',' ', $section)) }}</h3></div><div class="box-body">@foreach($permissions as $permission)<label class="display-block"><input type="checkbox" name="permissions[{{ $section }}][]" value="{{ $permission }}"> {{ $permission }}</label>@endforeach</div></div></div>@endforeach</div>
</div><div class="box-footer"><button class="btn btn-primary">Save Permission Matrix</button></div></div></form></section>
@endsection
