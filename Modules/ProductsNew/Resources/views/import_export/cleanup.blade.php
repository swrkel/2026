@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="pn-page-title"><div><h3>Data Cleanup Centre</h3><p>Find incomplete, duplicate and risky product master records before they affect sales or stock.</p></div></div>
<div class="row"><div class="col-md-4"><div class="pn-card"><div class="pn-card-header"><strong>Create Cleanup Task</strong></div><div class="pn-card-body"><form method="POST" action="{{ route('products-new.data-cleanup.store') }}">@csrf
<select name="task_type" class="form-control">@foreach($availableTasks as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select><br><button class="btn btn-primary">Create Task</button></form></div></div></div>
<div class="col-md-8"><div class="pn-card"><div class="pn-card-header"><strong>Recent Tasks</strong></div><div class="pn-card-body table-responsive"><table class="table table-bordered"><thead><tr><th>ID</th><th>Task</th><th>Status</th><th>Created</th></tr></thead><tbody>@forelse($tasks as $task)<tr><td>{{ $task->id }}</td><td>{{ $task->task_type }}</td><td>{{ $task->status }}</td><td>{{ $task->created_at }}</td></tr>@empty<tr><td colspan="4" class="text-center pn-muted">No cleanup tasks yet.</td></tr>@endforelse</tbody></table></div></div></div></div>
@endsection
