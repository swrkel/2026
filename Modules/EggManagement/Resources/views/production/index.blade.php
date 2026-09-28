@extends('egg::layouts.app',['title'=>'Daily Egg Collection'])
@section('head_actions')<a class="egg-btn egg-btn-primary" href="{{ route('egg.production.create') }}">+ Add New</a>@endsection
@section('egg_content')
@include('egg::partials.toolbar')
<div class="egg-card egg-table-card"><div class="table-responsive"><table class="egg-table" data-egg-table><thead><tr><th>No</th><th>Date</th><th>Total</th><th>Good</th><th>Broken</th><th>Dirty</th><th>Rejected</th><th>Status</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->collection_no }}</td><td>{{ $row->collection_date }}</td><td>{{ $row->total_pieces }}</td><td>{{ $row->good_pieces }}</td><td>{{ $row->broken_pieces }}</td><td>{{ $row->dirty_pieces }}</td><td>{{ $row->rejected_pieces }}</td><td>{{ $row->status }}</td></tr>@empty<tr><td colspan="8" class="egg-empty">No records found.</td></tr>@endforelse</tbody></table></div></div>
@if(method_exists($rows,'links'))<div class="egg-pagination">{{ $rows->links() }}</div>@endif
@endsection
