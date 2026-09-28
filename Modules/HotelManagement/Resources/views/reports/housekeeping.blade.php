@extends('hotelmanagement::layouts.app')
@section('hotel_content')
<section class="content-header"><h1>Housekeeping Report <small>POS standard layout</small></h1></section>
<section class="content">
@include('hotelmanagement::partials.nav')
<div class="box"><div class="box-header"><h3 class="box-title">Filters</h3></div><div class="box-body">@include('hotelmanagement::reports.partials.filters')</div></div>
<div class="box"><div class="box-header"><h3 class="box-title">Report Records</h3></div><div class="box-body">
<input type="text" class="form-control hm-search-input" placeholder="Search..." style="max-width:260px;margin-bottom:12px;">
<div class="table-responsive"><table class="table hm-table table-striped"><thead><tr><th>ID</th><th>Reference</th><th>Status</th><th>Date</th><th>Amount</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td>{{ $row->id ?? '' }}</td><td>{{ $row->reservation_no ?? $row->folio_no ?? $row->room_no ?? $row->task_no ?? $row->reference_no ?? '-' }}</td><td><span class="hm-badge {{ $row->status ?? '' }}">{{ ucfirst(str_replace('_',' ',$row->status ?? '-')) }}</span></td><td>{{ $row->created_at ?? $row->arrival_date ?? $row->checkin_at ?? '' }}</td><td>{{ number_format($row->amount ?? $row->estimated_total ?? $row->balance ?? 0, 4) }}</td></tr>
@empty<tr><td colspan="5"><div class="hm-empty">No records found for selected date range.</div></td></tr>@endforelse
</tbody></table></div></div></div>
</section>
@endsection
