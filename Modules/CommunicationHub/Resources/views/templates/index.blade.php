@extends('layouts.app')
@section('title', $title ?? 'Communication Hub')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'Communication Hub' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

@php($title='Communication Templates')
<div class="box box-primary"><div class="box-header with-border"><a href="{{ route('communicationhub.templates.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Template</a></div><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Channel</th><th>Status</th><th>Action</th></tr></thead><tbody>@foreach($templates as $template)<tr><td>{{ $template->code }}</td><td>{{ $template->name }}</td><td>{{ $template->category }}</td><td>{{ strtoupper($template->channel) }}</td><td>{{ $template->is_active ? 'Active' : 'Inactive' }}</td><td><a href="{{ route('communicationhub.templates.edit',$template) }}" class="btn btn-xs btn-info">Edit</a></td></tr>@endforeach</tbody></table>{{ $templates->links() }}</div></div>
</section>
@endsection
