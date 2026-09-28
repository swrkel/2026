@php($title = $title ?? 'Beauty Saloons')
<section class="content-header"><h1>{{ $title }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<div class="box box-primary"><div class="box-body">
<form method="POST" action="{{ isset($record) ? route('beautysaloons.resources.update', $record->id) : route('beautysaloons.resources.store') }}">
 @csrf
 @if(isset($record)) @method('PUT') @endif
 <div class="form-group"><label>Resource Code</label><input type="text" name="resource_code" value="{{ old('resource_code', $record->resource_code ?? '') }}" class="form-control"></div>
<div class="form-group"><label>Resource Name</label><input type="text" name="resource_name" value="{{ old('resource_name', $record->resource_name ?? '') }}" class="form-control"></div>
<div class="form-group"><label>Resource Type</label><input type="text" name="resource_type" value="{{ old('resource_type', $record->resource_type ?? '') }}" class="form-control"></div>
<div class="form-group"><label>Capacity</label><input type="text" name="capacity" value="{{ old('capacity', $record->capacity ?? '') }}" class="form-control"></div>
 <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
 <button type="submit" class="btn btn-success btn-lg">Save</button>
 <a href="{{ route('beautysaloons.resources.index') }}" class="btn btn-default btn-lg">Cancel</a>
</form></div></div>
</section>

