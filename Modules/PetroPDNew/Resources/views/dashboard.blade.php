@extends('petropdnew::layouts.app')
@section('title','Petro PD-New Dashboard')
@section('page_title','Petro PD-New Dashboard')
@section('pdnew_content')
<div class="pdn-page-head">
    <div><h2>Settlement Operations</h2><p>Closed Pumper Dashboard-New shifts are the only operational source.</p></div>
    <div class="pdn-actions">
        @can('petro_pd_new.sources.view')<a class="pdn-btn light" href="{{ route('petro-pd-new.sources.index') }}">View PONE Shifts</a>@endcan
        @can('petro_pd_new.settlements.create')<a class="pdn-btn primary" href="{{ route('petro-pd-new.settlements.create') }}">Add PD Settlement</a>@endcan
    </div>
</div>
<div class="pdn-grid cards">
    @foreach([
        ['Available Closed Shifts',$cards['available_shifts'],'Ready for import'],
        ['Draft / Reopened',$cards['draft_settlements'],'Editable settlements'],
        ['Review / Approved',$cards['review_settlements'],'Awaiting control'],
        ['Finalized Today',$cards['finalized_today'],'Completed settlements'],
        ['Open Issues',$cards['open_issues'],'Reconciliation items'],
        ['Pending Integration',$cards['pending_integration'],'Internal event queue'],
    ] as $card)
        <div class="pdn-card pdn-kpi"><div class="pdn-kpi-label">{{ $card[0] }}</div><div class="pdn-kpi-value">{{ number_format($card[1]) }}</div><div class="pdn-kpi-note">{{ $card[2] }}</div></div>
    @endforeach
</div>
<div class="pdn-grid three" style="margin-top:14px">
    <div class="pdn-card"><h3>Expected Today</h3><div class="pdn-kpi-value">{{ number_format((float)($totals->expected ?? 0),4) }}</div></div>
    <div class="pdn-card"><h3>Received Today</h3><div class="pdn-kpi-value">{{ number_format((float)($totals->received ?? 0),4) }}</div></div>
    <div class="pdn-card"><h3>Variance Today</h3><div class="pdn-kpi-value">{{ number_format((float)($totals->variance ?? 0),4) }}</div></div>
</div>
<div class="pdn-card" style="margin-top:14px">
    <div class="pdn-page-head"><div><h3>Recent Settlements</h3><p>Latest Petro PD-New activity.</p></div></div>
    <div class="pdn-table-wrap"><table class="pdn-table"><thead><tr>
        <th>Settlement No</th><th>Date</th><th>PONE Shift</th><th>Operator</th><th>Status</th><th class="amount">Expected</th><th class="amount">Received</th><th class="amount">Variance</th><th>Action</th>
    </tr></thead><tbody>
    @forelse($recent as $row)<tr>
        <td><strong>{{ $row->settlement_number }}</strong></td><td>{{ optional($row->settlement_date)->format('d M Y') }}</td>
        <td>{{ $row->pone_shift_number }}</td><td>{{ $row->operator_name }}</td><td><span class="pdn-badge {{ $row->status }}">{{ $row->status }}</span></td>
        <td class="amount">{{ number_format((float)$row->expected_total,4) }}</td><td class="amount">{{ number_format((float)$row->received_total,4) }}</td><td class="amount">{{ number_format((float)$row->variance_amount,4) }}</td>
        <td><a class="pdn-btn small light" href="{{ route('petro-pd-new.settlements.show',$row->id) }}">Open</a></td>
    </tr>@empty<tr><td colspan="9" class="pdn-empty">No Petro PD-New settlements have been created.</td></tr>@endforelse
    </tbody></table></div>
</div>
@endsection
