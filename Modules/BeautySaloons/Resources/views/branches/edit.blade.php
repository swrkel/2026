@php($title = $title ?? 'Beauty Saloons')
<section class="content-header"><h1>{{ $title }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<div class="box box-primary"><div class="box-body">
<form method="POST" action="{{ isset($record) ? route('beautysaloons.branches.update', $record->id) : route('beautysaloons.branches.store') }}">
 @csrf
 @if(isset($record)) @method('PUT') @endif
 <div class="form-group"><label>Branch Code</label><input type="text" name="branch_code" value="{{ old('branch_code', $record->branch_code ?? '') }}" class="form-control"></div>
<div class="form-group"><label>Branch Name</label><input type="text" name="name" value="{{ old('name', $record->name ?? '') }}" class="form-control"></div>
<div class="form-group"><label>Manager Name</label><input type="text" name="manager_name" value="{{ old('manager_name', $record->manager_name ?? '') }}" class="form-control"></div>
<div class="form-group"><label>Phone</label><input type="text" name="phone" value="{{ old('phone', $record->phone ?? '') }}" class="form-control"></div>
<div class="form-group"><label>Email</label><input type="text" name="email" value="{{ old('email', $record->email ?? '') }}" class="form-control"></div>
<div class="form-group"><label>Address</label><input type="text" name="address" value="{{ old('address', $record->address ?? '') }}" class="form-control"></div>
 <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
 <button type="submit" class="btn btn-success btn-lg">Save</button>
 <a href="{{ route('beautysaloons.branches.index') }}" class="btn btn-default btn-lg">Cancel</a>
</form></div></div>
</section>

