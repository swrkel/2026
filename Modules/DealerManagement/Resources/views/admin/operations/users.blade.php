@extends('layouts.app')
@section('title','Dealer Users')
@section('content')
@include('dealermanagement::partials.system-standard')
<section class="content-header"><h1>Dealer Users</h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Dealer</th><th>User Name</th><th>Login Code</th><th>Role</th><th>Mobile</th><th>Email</th><th>Dealer Admin</th><th>Status</th><th>Last Login</th><th>Note</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td>{{ $r->dealer_code }} - {{ $r->dealer_name }}</td><td>{{ $r->name }}</td><td>{{ $r->login_code }}</td><td>{{ $r->role_name ?: '-' }}</td><td>{{ $r->mobile ?: '-' }}</td><td>{{ $r->email ?: '-' }}</td><td>{{ $r->is_dealer_admin ? 'Yes':'No' }}</td><td>{{ $r->is_active ? 'Active':'Inactive' }}</td><td>{{ $r->last_login_at ?: '-' }}</td><td>{{ $r->notes ?: '-' }}</td></tr>
@empty<tr><td colspan="10" class="text-center">No dealer users available yet. Users created by dealer administrators will appear here.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div></div></section>
@endsection
