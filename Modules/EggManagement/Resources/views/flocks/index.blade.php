@extends('egg::layouts.app',['title'=>'Layer Flocks'])
@section('head_actions')<a class="egg-btn egg-btn-primary" href="{{ route('egg.flocks.create') }}">+ Add New</a>@endsection
@section('egg_content')
@include('egg::partials.toolbar')
<div class="egg-card egg-table-card"><div class="table-responsive"><table class="egg-table" data-egg-table><thead><tr><th>Code</th><th>Name</th><th>Breed</th><th>Bird Count</th><th>Started</th><th>Active</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->flock_code }}</td><td>{{ $row->name }}</td><td>{{ $row->breed }}</td><td>{{ $row->bird_count }}</td><td>{{ $row->started_on }}</td><td>{{ $row->active }}</td></tr>@empty<tr><td colspan="6" class="egg-empty">No records found.</td></tr>@endforelse</tbody></table></div></div>
@if(method_exists($rows,'links'))<div class="egg-pagination">{{ $rows->links() }}</div>@endif
@endsection
