<div class="pdn-grid two">
<div><h3>Status History</h3><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Changed At</th><th>From</th><th>To</th><th>Reason</th><th>User</th></tr></thead><tbody>
@forelse($settlement->history->sortByDesc('changed_at') as $row)<tr><td>{{ optional($row->changed_at)->format('d M Y H:i') }}</td><td>{{ $row->from_status ?: '—' }}</td><td><span class="pdn-badge {{ $row->to_status }}">{{ $row->to_status }}</span></td><td>{{ $row->reason ?: '—' }}</td><td>{{ $row->changed_by }}</td></tr>@empty<tr><td colspan="5" class="pdn-empty">No status history.</td></tr>@endforelse
</tbody></table></div></div>
<div><h3>Approval History</h3><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Action</th><th>Transition</th><th>At</th><th>User</th><th>Note</th></tr></thead><tbody>
@forelse($settlement->approvals->sortByDesc('id') as $row)<tr><td>{{ $row->action }}</td><td><span class="pdn-badge {{ $row->to_status }}">{{ $row->from_status }} → {{ $row->to_status }}</span></td><td>{{ optional($row->acted_at)->format('d M Y H:i') }}</td><td>{{ $row->acted_by }}</td><td>{{ $row->note ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="pdn-empty">No approval history.</td></tr>@endforelse
</tbody></table></div></div>
</div>
