@extends('digitalwallet::layout')
@section('digitalwallet-title', 'Wallet Types')
@section('digitalwallet-content')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Wallet Type Register</h3><div class="pull-right"><form method="POST" action="{{ route('digitalwallet.types.seed-defaults') }}" style="display:inline">@csrf<button class="btn btn-warning btn-sm">Create Default Types</button></form> <a href="{{ route('digitalwallet.types.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Type</a></div></div><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Code</th><th>Name</th><th>Channel</th><th>Currency</th><th>Default</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($types as $type)<tr><td>{{ $type->type_code }}</td><td>{{ $type->type_name }}</td><td>{{ $type->channel ?: '-' }}</td><td>{{ $type->currency }}</td><td>{{ $type->is_default ? 'Yes' : 'No' }}</td><td>{{ $type->is_active ? 'Active' : 'Inactive' }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('digitalwallet.types.edit', $type) }}">Edit</a></td></tr>@empty<tr><td colspan="7" class="text-center">No wallet types found.</td></tr>@endforelse
</tbody></table>{{ $types->links() }}
</div></div>
@endsection
