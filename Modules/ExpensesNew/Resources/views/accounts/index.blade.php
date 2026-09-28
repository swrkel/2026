@extends('expensesnew::layouts.app')
@section('title','Expense Accounts')
@section('content')
<div class="expnew-card">
  <div class="expnew-card-header"><h3>Expense Accounts</h3><div class="expnew-actions"><form method="post" action="{{ route('expensesnew.accounts.sync') }}">@csrf<button class="btn btn-warning">Sync Accounts</button></form><a href="{{ route('expensesnew.accounts.create') }}" class="btn btn-primary">+ Add</a></div></div>
  @include('expensesnew::components.toolbar')
  <table id="expnew_accounts_table" class="table table-bordered table-striped">
    <thead><tr><th>Name</th><th>Code</th><th>Status</th><th>Action</th></tr></thead>
  </table>
</div>
@endsection
@push('scripts')
<script>EXPNEW.datatable('#expnew_accounts_table','{{ route('expensesnew.accounts.data') }}',[{data:'name'},{data:'code'},{data:'active'},{data:'action',orderable:false,searchable:false}]);</script>
@endpush
