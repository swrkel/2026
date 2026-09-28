@extends('egg::layouts.app',['title'=>'Egg Stock'])
@section('egg_content')
<form method="get" class="egg-date-filter no-print"><label>Grade <select name="grade_id"><option value="">All grades</option>@foreach($grades as $g)<option value="{{ $g->id }}" @selected(request('grade_id')==$g->id)>{{ $g->name }}</option>@endforeach</select></label><button class="egg-btn egg-btn-primary">Apply</button></form>
@include('egg::partials.toolbar')
<div class="egg-card"><div class="table-responsive"><table class="egg-table" data-egg-table><thead><tr><th>Lot</th><th>Collection Date</th><th>Grade ID</th><th>Received</th><th>Available</th><th>Unit Cost</th><th>Best Before</th><th>Status</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->lot_no }}</td><td>{{ $row->collection_date }}</td><td>{{ $row->grade_id }}</td><td>{{ $row->received_pieces }}</td><td>{{ $row->available_pieces }}</td><td>{{ number_format($row->unit_cost,4) }}</td><td>{{ $row->best_before }}</td><td>{{ $row->status }}</td></tr>@empty<tr><td colspan="8" class="egg-empty">No stock available.</td></tr>@endforelse</tbody></table></div></div><div class="egg-pagination">{{ $rows->links() }}</div>
@endsection
