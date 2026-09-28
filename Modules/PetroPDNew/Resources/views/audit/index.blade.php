@extends('petropdnew::layouts.app')
@section('title','Petro PD-New User Activity')
@section('page_title','User Activity')
@section('pdnew_content')
<div class="pdn-page-head"><div><h2>User Activity</h2><p>Immutable audit records for Petro PD-New actions.</p></div></div>
<form method="get" class="pdn-toolbar"><div class="pdn-field grow"><label>Action</label><input class="pdn-input" name="action" value="{{ request('action') }}"></div><div class="pdn-field"><label>User ID</label><input class="pdn-input" type="number" name="user_id" value="{{ request('user_id') }}"></div><div class="pdn-field"><label>From</label><input class="pdn-input" type="date" name="date_from" value="{{ request('date_from') }}"></div><div class="pdn-field"><label>To</label><input class="pdn-input" type="date" name="date_to" value="{{ request('date_to') }}"></div><button class="pdn-btn primary">Apply</button></form>
<div class="pdn-card"><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Time</th><th>User</th><th>Location</th><th>Action</th><th>Entity</th><th>IP</th><th>Changes</th></tr></thead><tbody>
@forelse($logs as $row)<tr><td>{{ optional($row->created_at)->format('d M Y H:i:s') }}</td><td>{{ $row->user_id ?: 'System' }}</td><td>{{ $row->location_id ?: '—' }}</td><td><strong>{{ $row->action }}</strong></td><td>{{ $row->entity_type }} #{{ $row->entity_id }}</td><td>{{ $row->ip_address }}</td><td><details><summary>View</summary><pre class="pdn-code">{{ json_encode(['old'=>$row->old_values,'new'=>$row->new_values],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></details></td></tr>@empty<tr><td colspan="7" class="pdn-empty">No activity records.</td></tr>@endforelse
</tbody></table></div><div class="pdn-pagination">{{ $logs->links() }}</div></div>
@endsection
