@extends('petropdnew::layouts.app')
@section('title','PD Closed Shifts')
@section('page_title','Pumper Dashboard-New Closed Shifts')
@section('pdnew_content')
<div class="pdn-page-head">
    <div><h2>Closed Shift Source</h2><p>Only closed Pumper Dashboard-New shifts can enter Petro PD-New.</p></div>
    @can('petro_pd_new.settlements.create')<a class="pdn-btn primary" href="{{ route('petro-pd-new.settlements.create') }}">Add PD Settlement</a>@endcan
</div>
<form class="pdn-toolbar" method="get">
    <div class="pdn-field"><label>Status</label><select class="pdn-select" name="status">
        <option value="">All</option><option value="available" @selected(request('status')==='available')>Available</option>
        <option value="imported" @selected(request('status')==='imported')>Imported</option><option value="settled" @selected(request('status')==='settled')>Settlement Created</option>
    </select></div>
    <div class="pdn-field"><label>From</label><input class="pdn-input" type="date" name="date_from" value="{{ request('date_from') }}"></div>
    <div class="pdn-field"><label>To</label><input class="pdn-input" type="date" name="date_to" value="{{ request('date_to') }}"></div>
    <div class="pdn-field"><label>Operator Profile ID</label><input class="pdn-input" type="number" name="operator_profile_id" value="{{ request('operator_profile_id') }}"></div>
    <button class="pdn-btn primary">Apply</button><a class="pdn-btn light" href="{{ route('petro-pd-new.sources.index') }}">Reset</a>
</form>
<div class="pdn-card">
<div class="pdn-table-wrap"><table class="pdn-table"><thead><tr>
    <th>Shift No</th><th>Closed At</th><th>Operator</th><th>Location</th><th class="amount">Meter Sales</th><th class="amount">Other Sales</th><th class="amount">Payments</th><th>Import</th><th>PONE Reference</th><th>Action</th>
</tr></thead><tbody>
@forelse($shifts as $shift)<tr>
    <td><strong>{{ $shift->shift_number }}</strong></td><td>{{ $shift->closed_at }}</td><td>{{ $shift->operator_name ?: 'Operator #'.$shift->operator_profile_id }}</td><td>{{ $shift->location_id ?: '—' }}</td>
    <td class="amount">{{ number_format((float)$shift->meter_sales_total,4) }}</td><td class="amount">{{ number_format((float)$shift->other_sales_total,4) }}</td><td class="amount">{{ number_format((float)$shift->payments_total,4) }}</td>
    <td><span class="pdn-badge {{ $shift->import_status ?: 'draft' }}">{{ $shift->import_status ?: 'available' }}</span></td>
    <td>{{ $shift->pone_settlement_no ?: '—' }}</td>
    <td><div class="pdn-inline">
        <a class="pdn-btn small light" href="{{ route('petro-pd-new.sources.show',$shift->id) }}">View</a>
        @if(!$shift->settlement_id && !$shift->pone_settlement_no)
            @can('petro_pd_new.sources.import')
            <form method="post" action="{{ route('petro-pd-new.sources.import',$shift->id) }}">@csrf<button class="pdn-btn small purple">Import</button></form>
            @endcan
            @can('petro_pd_new.settlements.create')
            <form method="post" action="{{ route('petro-pd-new.sources.create-settlement',$shift->id) }}" data-prevent-double-submit>@csrf
                <input type="hidden" name="settlement_date" value="{{ now()->toDateString() }}"><button class="pdn-btn small success">Create Settlement</button>
            </form>
            @endcan
        @elseif($shift->settlement_id)
            <a class="pdn-btn small success" href="{{ route('petro-pd-new.settlements.show',$shift->settlement_id) }}">Open Settlement</a>
        @else
            <span class="pdn-badge finalized">Already finalized: {{ $shift->pone_settlement_no }}</span>
        @endif
    </div></td>
</tr>@empty<tr><td colspan="10" class="pdn-empty">No closed Pumper Dashboard-New shifts match the selected filters.</td></tr>@endforelse
</tbody></table></div>
<div class="pdn-pagination">{{ $shifts->links() }}</div>
</div>
@endsection
