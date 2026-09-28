@extends('expensesnew::layouts.app')
@section('title', $account->exists ? 'Edit Expense Account' : 'Add Expense Account')
@section('content')
<form method="post" action="{{ $account->exists ? route('expensesnew.accounts.update',$account->id) : route('expensesnew.accounts.store') }}" class="expnew-card expnew-form">
 @csrf @if($account->exists) @method('PUT') @endif
 <div class="expnew-card-header"><h3>{{ $account->exists ? 'Edit' : 'Add' }} Expense Account</h3></div>
 <div class="row"><div class="col-md-6"><label>Name *</label><input class="form-control" name="name" required value="{{ old('name',$account->name) }}"></div><div class="col-md-3"><label>Code</label><input class="form-control" name="code" value="{{ old('code',$account->code) }}"></div><div class="col-md-3"><label>Status</label><br><label><input type="checkbox" name="is_active" {{ old('is_active',$account->is_active ?? 1) ? 'checked' : '' }}> Active</label></div></div>
 <div class="row mt-2"><div class="col-md-12"><label>Description</label><textarea name="description" class="form-control">{{ old('description',$account->description) }}</textarea></div></div>
 <div class="expnew-form-footer"><button class="btn btn-success">Save</button><a href="{{ route('expensesnew.accounts.index') }}" class="btn btn-secondary">Close</a></div>
</form>
@endsection
