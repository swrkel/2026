@extends('layouts.app')
@section('title','Dealer Roles')
@section('content')
@include('dealermanagement::partials.system-standard')
<section class="content-header"><h1>Dealer Roles</h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Dealer</th><th>Role</th><th>System Role</th><th>No. of Permissions</th><th>Created</th><th>Note</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td>{{ $r->dealer_code }} - {{ $r->dealer_name }}</td><td>{{ $r->name }}</td><td>{{ $r->is_system ? 'Yes':'No' }}</td><td>{{ $r->permissions_count }}</td><td>{{ $r->created_at ?: '-' }}</td><td>{{ $r->notes ?: '-' }}</td></tr>
@empty<tr><td colspan="6" class="text-center">No dealer roles available yet. Dealer roles will appear after dealers are created.</td></tr>@endforelse
</tbody></table>{{ $rows->links() }}</div></div></section>
@endsection
