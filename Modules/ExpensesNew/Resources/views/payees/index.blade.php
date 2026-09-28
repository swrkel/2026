@extends('expensesnew::layouts.app', ['heading'=>'Payees'])
@section('module_content')
<div class="expnew-card-header"><h3>Payees</h3><div class="expnew-actions"><form method="post" action="{{ route('expensesnew.payees.sync_cheque') }}">@csrf<button class="btn btn-warning">Sync Cheque Payees</button></form><a class="btn btn-primary" href="{{ route('expensesnew.payees.create') }}">+ Add</a></div></div>
@include('expensesnew::components.toolbar')
<table class="table table-bordered table-striped expnew-datatable" data-url="{{ route('expensesnew.payees.data') }}"><thead><tr><th>Name</th><th>Mobile</th><th>Email</th><th>Status</th><th>Action</th></tr></thead></table>
@endsection
