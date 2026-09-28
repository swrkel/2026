@extends('layouts.app')
@section('title','Asset Types')
@section('content')
<section class="content-header no-print"><h1>Asset Types</h1></section>
<section class="content no-print">@include('leasing::layouts.nav')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Asset Types</h3><div class="box-tools"><a href="{{ route('leasing.collateral-types.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add</a></div></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Name</th><th>Code</th><th>Weight</th><th>Purity</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($types as $type)<tr><td>{{ $type->name }}</td><td>{{ $type->code }}</td><td>{{ $type->requires_weight ? 'Yes':'No' }}</td><td>{{ $type->requires_purity ? 'Yes':'No' }}</td><td>{{ $type->status ? 'Active':'Inactive' }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('leasing.collateral-types.edit',$type->id) }}">Edit</a></td></tr>@empty<tr><td colspan="6" class="text-center">No records found</td></tr>@endforelse</tbody></table>{{ $types->links() }}</div></div>
</section>
@endsection
