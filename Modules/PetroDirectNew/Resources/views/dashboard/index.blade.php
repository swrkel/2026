@extends('layouts.app')
@section('title','Petro Direct-New')
@section('content')
<div class="pdirectnew-module container-fluid py-3">
    @include('petrodirectnew::partials.styles')
    <div class="pdn-page-head"><div><h1>Petro Direct-New Dashboard</h1><p>Direct fuel-station settlement and pumper operations.</p></div><div><a class="btn btn-primary" href="{{ route('petro-direct-new.settlements.create') }}">Add Direct Settlement</a></div></div>
    <form class="pdn-card pdn-toolbar" method="get"><div><label>Business Location</label><select name="location_id" class="form-control"><option value="">All Permitted Locations</option>@foreach($locations as $loc)<option value="{{ $loc->id }}" @selected($locationId==$loc->id)>{{ $loc->name }}</option>@endforeach</select></div><button class="btn btn-primary">Apply</button></form>
    <div class="pdn-grid">
        @foreach(['active_operators'=>'Active Operators','open_shifts'=>'Open Shifts','open_assignments'=>'Open Assignments','draft_settlements'=>'Draft Settlements','finalized_today'=>'Finalized Today','expected_today'=>'Expected Today','received_today'=>'Received Today','variance_today'=>'Variance Today'] as $key=>$label)
        <div class="pdn-card pdn-stat"><span>{{ $label }}</span><strong>{{ in_array($key,['expected_today','received_today','variance_today']) ? number_format($summary[$key],4) : $summary[$key] }}</strong></div>
        @endforeach
    </div>
    <div class="pdn-card"><h3>Recent Direct Settlements</h3><div class="pdn-scroll"><table class="table table-bordered"><thead><tr><th>No</th><th>Date</th><th>Operator</th><th>Status</th><th class="pdn-money">Expected</th><th class="pdn-money">Received</th><th class="pdn-money">Variance</th><th>Action</th></tr></thead><tbody>@forelse($summary['recent_settlements'] as $row)<tr><td>{{ $row->settlement_no }}</td><td>{{ optional($row->transaction_date)->format('d/m/Y') }}</td><td>{{ optional($row->operator)->name }}</td><td><span class="pdn-status {{ $row->status }}">{{ $row->status }}</span></td><td class="pdn-money">{{ number_format($row->expected_total,4) }}</td><td class="pdn-money">{{ number_format($row->received_total,4) }}</td><td class="pdn-money">{{ number_format($row->variance,4) }}</td><td><a class="btn btn-sm btn-info" href="{{ route('petro-direct-new.settlements.show',$row) }}">View</a></td></tr>@empty<tr><td colspan="8" class="text-center">No direct settlements found.</td></tr>@endforelse</tbody></table></div></div>
</div>
@endsection
