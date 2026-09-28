@extends('egg::layouts.app',['title'=>'Grading & Packing'])
@section('head_actions')<a class="egg-btn egg-btn-primary" href="{{ route('egg.grading.create') }}">+ Add New</a>@endsection
@section('egg_content')
@include('egg::partials.toolbar')
<div class="egg-card egg-table-card"><div class="table-responsive"><table class="egg-table" data-egg-table><thead><tr><th>No</th><th>Date</th><th>Collection</th><th>Input</th><th>Output</th><th>Status</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->grading_no }}</td><td>{{ $row->graded_on }}</td><td>{{ $row->collection_id }}</td><td>{{ $row->input_pieces }}</td><td>{{ $row->output_pieces }}</td><td>{{ $row->status }}</td></tr>@empty<tr><td colspan="6" class="egg-empty">No records found.</td></tr>@endforelse</tbody></table></div></div>
@if(method_exists($rows,'links'))<div class="egg-pagination">{{ $rows->links() }}</div>@endif
@endsection
