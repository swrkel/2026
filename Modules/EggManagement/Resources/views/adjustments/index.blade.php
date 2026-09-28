@extends('egg::layouts.app',['title'=>'Adjustments / Wastage'])
@section('head_actions')<a class="egg-btn egg-btn-primary" href="{{ route('egg.adjustments.create') }}">+ Add New</a>@endsection
@section('egg_content')
@include('egg::partials.toolbar')
<div class="egg-card egg-table-card"><div class="table-responsive"><table class="egg-table" data-egg-table><thead><tr><th>No</th><th>Date</th><th>Reason</th><th>Status</th><th>Note</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->adjustment_no }}</td><td>{{ $row->adjustment_date }}</td><td>{{ $row->reason }}</td><td>{{ $row->status }}</td><td>{{ $row->note }}</td></tr>@empty<tr><td colspan="5" class="egg-empty">No records found.</td></tr>@endforelse</tbody></table></div></div>
@if(method_exists($rows,'links'))<div class="egg-pagination">{{ $rows->links() }}</div>@endif
@endsection
