@extends('autoservice::layouts.master')
@section('title','Service Packages')
@section('autoservice_content')
<div class="box"><div class="box-header with-border"><h3 class="box-title">Service Package Manager</h3>
<div class="box-tools"><a href="{{ route('autoservice.packages.create') }}" class="btn btn-primary btn-sm">Add Package</a></div></div>
<div class="box-body">
<form class="row" method="get"><div class="col-md-4"><input name="search" class="form-control" value="{{ request('search') }}" placeholder="Search package/code"></div>
<div class="col-md-3"><select name="category_id" class="form-control"><option value="">All Categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-default">Filter</button></div></form><hr>
<table class="table table-bordered table-striped"><thead><tr><th>Code</th><th>Package</th><th>Category</th><th>Stock Parts</th><th>Labour</th><th>Non-stock</th><th>Selling Price</th><th>Status</th><th>Action</th></tr></thead>
<tbody>@forelse($packages as $p)<tr><td>{{ $p->package_code }}</td><td>{{ $p->name }}</td><td>{{ optional($p->category)->name }}</td>
<td class="text-right">{{ number_format($p->parts_amount,2) }}</td><td class="text-right">{{ number_format($p->labour_amount,2) }}</td><td class="text-right">{{ number_format($p->non_stock_amount,2) }}</td><td class="text-right">{{ number_format($p->selling_price ?: $p->total_amount,2) }}</td>
<td>{{ $p->is_active?'Active':'Inactive' }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('autoservice.packages.edit',$p->id) }}">Edit</a></td></tr>@empty<tr><td colspan="9" class="text-center">No packages found.</td></tr>@endforelse</tbody></table>
{{ $packages->links() }}</div></div>
<div class="box"><div class="box-header"><h3 class="box-title">Quick Add Category</h3></div><div class="box-body"><form method="post" action="{{ route('autoservice.package_categories.store') }}" class="form-inline">@csrf
<input name="code" class="form-control" placeholder="Code"><input name="name" required class="form-control" placeholder="Category name"><button class="btn btn-success">Save Category</button></form></div></div>
@endsection
