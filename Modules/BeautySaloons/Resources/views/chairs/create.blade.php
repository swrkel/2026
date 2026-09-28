@php($title = $title ?? 'Beauty Saloons')
<section class="content-header"><h1>{{ $title }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<div class="box box-primary"><div class="box-body">
<form method="POST" action="{{ isset($record) ? route('beautysaloons.chairs.update', $record->id) : route('beautysaloons.chairs.store') }}">
 @csrf
 @if(isset($record)) @method('PUT') @endif
 <div class="form-group"><label>Chair Code</label><input type="text" name="chair_code" value="{{ old('chair_code', $record->chair_code ?? '') }}" class="form-control"></div>
<div class="form-group"><label>Chair Name</label><input type="text" name="chair_name" value="{{ old('chair_name', $record->chair_name ?? '') }}" class="form-control"></div>
<div class="form-group"><label>Chair Type</label><input type="text" name="chair_type" value="{{ old('chair_type', $record->chair_type ?? '') }}" class="form-control"></div>
<div class="form-group"><label>Floor</label><input type="text" name="floor_name" value="{{ old('floor_name', $record->floor_name ?? '') }}" class="form-control"></div>
<div class="form-group"><label>Zone</label><input type="text" name="zone_name" value="{{ old('zone_name', $record->zone_name ?? '') }}" class="form-control"></div>
 <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
 <button type="submit" class="btn btn-success btn-lg">Save</button>
 <a href="{{ route('beautysaloons.chairs.index') }}" class="btn btn-default btn-lg">Cancel</a>
</form></div></div>
</section>

