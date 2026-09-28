@extends('distributionnew::layouts.app')
@section('content')
@include('distributionnew::partials.erp-standard-styles')
<div class="pos-card">
    <div class="pos-card-header"><h4>@lang('distributionnew::messages.territories')</h4></div>
    <form method="POST" action="{{ route('distributionnew.territories.store') }}" class="row">@csrf
        <div class="col-md-3"><input name="code" class="form-control" placeholder="Territory Code"></div>
        <div class="col-md-4"><input name="name" class="form-control" placeholder="Territory Name" required></div>
        <div class="col-md-4"><input name="description" class="form-control" placeholder="Description"></div>
        <div class="col-md-1"><button class="btn btn-primary btn-block">Save</button></div>
    </form>
    <hr>
    <table class="table table-bordered table-striped disnew-table"><thead><tr><th>Code</th><th>Name</th><th>Description</th><th>Status</th></tr></thead><tbody>
    @foreach($territories as $territory)<tr><td>{{ $territory->code }}</td><td>{{ $territory->name }}</td><td>{{ $territory->description }}</td><td>{{ $territory->is_active ? 'Active' : 'Inactive' }}</td></tr>@endforeach
    </tbody></table>
</div>
@endsection
