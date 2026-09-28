@extends('petropdnew::layouts.app')
@section('title','Petro PD-New Settlements')
@section('page_title','Petro PD-New Settlements')
@section('pdnew_content')
<div class="pdn-page-head">
    <div><h2>List PD Settlements</h2><p>Settlement control, review, approval and finalization.</p></div>
    @can('petro_pd_new.settlements.create')<a class="pdn-btn primary" href="{{ route('petro-pd-new.settlements.create') }}">Add PD Settlement</a>@endcan
</div>
<form class="pdn-toolbar" method="get">
    <div class="pdn-field grow"><label>Search</label><input class="pdn-input" name="search" value="{{ request('search') }}" placeholder="Settlement, shift or operator"></div>
    <div class="pdn-field"><label>Status</label><select class="pdn-select" name="status"><option value="">All</option>
        @foreach(config('petropdnew.statuses',[]) as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach
    </select></div>
    <div class="pdn-field"><label>From</label><input class="pdn-input" type="date" name="date_from" value="{{ request('date_from') }}"></div>
    <div class="pdn-field"><label>To</label><input class="pdn-input" type="date" name="date_to" value="{{ request('date_to') }}"></div>
    <button class="pdn-btn primary">Apply</button><a class="pdn-btn light" href="{{ route('petro-pd-new.settlements.index') }}">Reset</a>
</form>
<div class="pdn-card">
<div class="pdn-table-wrap"><table class="pdn-table"><thead><tr>
    <th>Settlement No</th><th>Date</th><th>PONE Shift</th><th>Operator</th><th>Status</th><th>Reconciliation</th>
    <th class="amount">Expected</th><th class="amount">Collections</th><th class="amount">Shortage</th><th class="amount">Excess</th><th class="amount">Unresolved</th><th>Action</th>
</tr></thead><tbody>
@forelse($settlements as $row)<tr>
    <td><strong>{{ $row->settlement_number }}</strong></td><td>{{ optional($row->settlement_date)->format('d M Y') }}</td><td>{{ $row->pone_shift_number }}</td><td>{{ $row->operator_name }}</td>
    <td><span class="pdn-badge {{ $row->status }}">{{ $row->status }}</span></td><td><span class="pdn-badge {{ $row->reconciliation_status }}">{{ $row->reconciliation_status }}</span></td>
    <td class="amount">{{ number_format((float)$row->expected_total,4) }}</td><td class="amount">{{ number_format((float)$row->received_total,4) }}</td><td class="amount">{{ number_format((float)$row->source_shortage_total + (float)$row->manual_shortage_total,4) }}</td><td class="amount">{{ number_format((float)$row->source_excess_total + (float)$row->manual_excess_total,4) }}</td><td class="amount">{{ number_format((float)$row->variance_amount,4) }}</td>
    <td><div class="pdn-inline"><a class="pdn-btn small primary" href="{{ route('petro-pd-new.settlements.show',$row->id) }}">View</a>
        <a class="pdn-btn small light" target="_blank" href="{{ route('petro-pd-new.settlements.print',$row->id) }}">Print</a>
    </div></td>
</tr>@empty<tr><td colspan="12" class="pdn-empty">No Petro PD-New settlements match the selected filters.</td></tr>@endforelse
</tbody></table></div><div class="pdn-pagination">{{ $settlements->links() }}</div>
</div>
@endsection
