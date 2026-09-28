@extends('tailoring::layouts.app')
@section('page_title', 'Tailoring Departments')
@section('tailoring_content')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Departments</h3></div><div class="box-body">
<form method="POST" action="{{ route('tailoring.settings.departments.store') }}">@csrf
<div class="row"><div class="col-md-4"><input class="form-control" name="name" placeholder="Cutting / Stitching / QC" required></div><div class="col-md-2"><input class="form-control" name="code" placeholder="Code"></div><div class="col-md-2"><input type="number" class="form-control" name="sort_order" placeholder="Sort"></div><div class="col-md-2"><button class="btn btn-primary">Add</button></div></div></form><hr>
<table class="table table-bordered"><thead><tr><th>Name</th><th>Code</th><th>Sort</th><th>Status</th></tr></thead><tbody>@forelse($departments as $d)<tr><td>{{ $d->name }}</td><td>{{ $d->code }}</td><td>{{ $d->sort_order }}</td><td>{{ $d->is_active ? 'Active' : 'Inactive' }}</td></tr>@empty<tr><td colspan="4" class="text-center">No departments</td></tr>@endforelse</tbody></table>
</div></div>
@endsection
