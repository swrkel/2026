@extends('digitalwallet::layout')
@section('digitalwallet-title', 'Wallet Rules')
@section('digitalwallet-content')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Spending Rules</h3><a href="{{ route('digitalwallet.rules.create') }}" class="btn btn-primary btn-sm pull-right">Add Rule</a></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Name</th><th>Type</th><th>Wallet Type</th><th>Minimum Balance</th><th>Daily Limit</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($rules as $rule)
<tr><td>{{ $rule->rule_name }}</td><td>{{ $rule->rule_type }}</td><td>{{ $rule->wallet_type }}</td><td>{{ number_format($rule->minimum_balance ?? 0, 2) }}</td><td>{{ number_format($rule->daily_limit ?? 0, 2) }}</td><td>{{ $rule->is_active ? 'Active' : 'Inactive' }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('digitalwallet.rules.edit', $rule) }}">Edit</a></td></tr>
@empty<tr><td colspan="7" class="text-center">No rules found.</td></tr>@endforelse
</tbody></table>{{ $rules->links() }}</div></div>
@endsection
