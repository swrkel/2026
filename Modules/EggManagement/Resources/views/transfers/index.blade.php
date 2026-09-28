@extends('egg::layouts.app',['title'=>'Egg Stock Transfers'])
@section('head_actions')<a class="egg-btn egg-btn-primary" href="{{ route('egg.transfers.create') }}">+ Add New</a>@endsection
@section('egg_content')
@include('egg::partials.toolbar')
<div class="egg-card egg-table-card"><div class="table-responsive"><table class="egg-table" data-egg-table><thead><tr><th>Transfer No</th><th>Date</th><th>From Location</th><th>From Store</th><th>To Location</th><th>To Store</th><th>Status</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->transfer_no }}</td><td>{{ $row->transfer_date }}</td><td>{{ $row->from_location_id }}</td><td>{{ $row->from_store_id }}</td><td>{{ $row->to_location_id }}</td><td>{{ $row->to_store_id }}</td><td>{{ $row->status }}</td></tr>@empty<tr><td colspan="7" class="egg-empty">No records found.</td></tr>@endforelse</tbody></table></div></div>
@if(method_exists($rows,'links'))<div class="egg-pagination">{{ $rows->links() }}</div>@endif
@endsection
