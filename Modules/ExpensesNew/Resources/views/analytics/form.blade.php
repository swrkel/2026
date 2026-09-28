@extends('expensesnew::layouts.app')
@section('title', 'Expense Analytics')
@section('content')
<div class="expnew-page">
  <div class="expnew-header"><h3>Expense Analytics</h3></div>
  <div class="expnew-card expnew-form-card">
    <div class="row">
      <div class="col-md-4"><label>Name</label><input class="form-control" name="name"></div>
      <div class="col-md-4"><label>Code</label><input class="form-control" name="code"></div>
      <div class="col-md-4"><label>Status</label><select class="form-control"><option>Active</option><option>Inactive</option></select></div>
    </div>
    <div class="mt-3"><button class="expnew-btn expnew-btn-success">Save</button></div>
  </div>
</div>
@endsection
