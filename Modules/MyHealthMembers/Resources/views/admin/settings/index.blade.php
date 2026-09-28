@extends('layouts.app')
@section('title', 'My Health System Settings')
@section('content')
<section class="content-header"><h1>My Health System Settings</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.admin.settings.store') }}">@csrf<div class="box box-primary"><div class="box-body">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="row">@foreach($groups as $key => $label)<div class="col-md-6"><div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">{{ $label }}</h3></div><div class="box-body"><div class="form-group"><label>Enabled</label><select name="settings[{{ $key }}_enabled]" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div><div class="form-group"><label>Notes</label><input type="text" name="settings[{{ $key }}_notes]" class="form-control"></div></div></div></div>@endforeach</div>
</div><div class="box-footer"><button class="btn btn-primary">Save Settings</button></div></div></form></section>
@endsection
