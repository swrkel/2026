@php($rows = data_get($workspaceData, 'rows', collect()))
@php($totals = data_get($workspaceData, 'totals', []))
<div class="pdn-actions pdn-operator-context-actions no-print">
    @can('pumper_dashboard_new.shifts.view')<a class="pdn-btn light" href="{{ route('pumper-dashboard-new.admin.shifts.index') }}"><i class="fa fa-external-link"></i> Open Current Meter Control</a>@endcan
</div>
<div class="pdn-operator-summary-grid">
    <div class="pdn-operator-summary-card"><span>Visible Pumps</span><strong>{{ $rows->count() }}</strong></div>
    <div class="pdn-operator-summary-card"><span>Total Sold Quantity</span><strong>{{ number_format((float)($totals['sold_quantity'] ?? 0),3) }}</strong></div>
</div>
<div class="pdn-card"><div class="pdn-table-wrap"><table class="pdn-table"><thead><tr><th>Shift</th><th>Operator</th><th>Pump</th><th>Status</th><th class="amount">Opening Meter</th><th class="amount">Current Meter</th><th class="amount">Closing Meter</th><th class="amount">Testing Qty</th><th class="amount">Sold Qty</th><th>Last Updated</th><th>Action</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->shift_number }}</td><td>{{ $row->operator_name }}</td><td>Pump #{{ $row->pump_id }}</td><td><span class="pdn-badge {{ $row->status }}">{{ $row->status }}</span></td><td class="amount">{{ number_format((float)$row->opening_meter,3) }}</td><td class="amount"><strong>{{ number_format((float)$row->current_meter,3) }}</strong></td><td class="amount">{{ $row->closing_meter !== null ? number_format((float)$row->closing_meter,3) : '—' }}</td><td class="amount">{{ number_format((float)$row->testing_quantity,3) }}</td><td class="amount">{{ number_format((float)$row->sold_quantity,3) }}</td><td>{{ $row->updated_at ?: '—' }}</td><td>@if(Route::has('pumper-dashboard-new.admin.shifts.show'))<a class="pdn-btn small light" href="{{ route('pumper-dashboard-new.admin.shifts.show',$row->shift_id) }}">View Shift</a>@else—@endif</td></tr>
@empty<tr><td colspan="11" class="pdn-empty">No current-meter records were found.</td></tr>@endforelse
</tbody></table></div></div>
