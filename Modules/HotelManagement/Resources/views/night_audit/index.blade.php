@extends('hotelmanagement::layouts.app')

@section('hotel_content')
@include('hotelmanagement::partials.nav')
<section class="content-header hm-content-header">
    <h1>Night Audit <small>End-of-day revenue, collection, room status and open folio control</small></h1>
</section>
<section class="content">
    @if(session('status'))<div class="alert alert-success hm-alert">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger hm-alert">{{ $errors->first() }}</div>@endif

    <form method="GET" action="{{ route('hotel-management.night-audit.index') }}" class="hm-filter-bar" style="margin-bottom:14px">
        <input type="date" name="audit_date" class="form-control hm-date-input" value="{{ $auditDate }}">
        <button class="btn hm-btn-search">Preview</button>
    </form>

    <div class="row hm-dashboard-row">
        <div class="col-md-3"><div class="hm-stat-card"><span class="hm-stat-label">Total Revenue</span><strong>{{ number_format($preview['total_revenue'],2) }}</strong><small>Room + POS + events</small></div></div>
        <div class="col-md-3"><div class="hm-stat-card"><span class="hm-stat-label">Collections</span><strong>{{ number_format($preview['total_collected'],2) }}</strong><small>Cash/Card/Other</small></div></div>
        <div class="col-md-3"><div class="hm-stat-card"><span class="hm-stat-label">Open Folio Balance</span><strong>{{ number_format($preview['open_folio_balance'],2) }}</strong><small>Control before close</small></div></div>
        <div class="col-md-3"><div class="hm-stat-card"><span class="hm-stat-label">Rooms</span><strong>{{ $preview['occupied_rooms'] }} / {{ $preview['vacant_rooms'] }}</strong><small>Occupied / Vacant</small></div></div>
    </div>

    <div class="box hm-card hm-box">
        <div class="box-header with-border hm-box-header"><h3 class="box-title">Post Night Audit</h3></div>
        <div class="box-body">
            <form method="POST" action="{{ route('hotel-management.night-audit.run') }}">@csrf
                <div class="hm-form-grid">
                    <div class="form-group"><label>Audit Date</label><input type="date" name="audit_date" class="form-control" value="{{ $auditDate }}" required></div>
                    <div class="form-group"><label>Close Day After Posting</label><select name="close_open_day" class="form-control"><option value="1">Yes - Close Day</option><option value="0">No - Post Only</option></select></div>
                    <div class="form-group hm-col-span-2"><label>Audit Note</label><input name="note" class="form-control" placeholder="Optional end-of-day note, cash variance or manager remark"></div>
                </div><br>
                <button class="btn hm-btn-add">Post Night Audit</button>
            </form>
        </div>
    </div>

    <div class="box hm-card hm-box">
        <div class="box-header with-border hm-box-header"><h3 class="box-title">Audit Preview Breakdown</h3></div>
        <div class="box-body">
            @include('hotelmanagement::partials.toolbar')
            <div class="table-responsive"><table class="table table-bordered hm-table"><thead><tr><th>Section</th><th>Line</th><th>Reference</th><th class="text-right">Amount</th></tr></thead><tbody>
                @foreach($preview['lines'] as $line)<tr><td>{{ $line['section'] }}</td><td>{{ $line['label'] }}</td><td>{{ $line['value'] }}</td><td class="text-right">{{ number_format($line['amount'],2) }}</td></tr>@endforeach
            </tbody><tfoot><tr><th colspan="3" class="text-right">Total Revenue</th><th class="text-right">{{ number_format($preview['total_revenue'],2) }}</th></tr></tfoot></table></div>
        </div>
    </div>

    <div class="box hm-card hm-box">
        <div class="box-header with-border hm-box-header"><h3 class="box-title">Night Audit Register</h3></div>
        <div class="box-body">
            <div class="table-responsive"><table class="table table-bordered hm-table"><thead><tr><th>Audit No</th><th>Date</th><th>Revenue</th><th>Collected</th><th>Open Balance</th><th>Arrivals</th><th>Departures</th><th>Rooms</th><th>Status</th><th>Action</th></tr></thead><tbody>
                @forelse($audits as $audit)<tr>
                    <td>{{ $audit->audit_no }}</td><td>{{ $audit->audit_date }}</td><td>{{ number_format($audit->total_revenue,2) }}</td><td>{{ number_format($audit->total_collected,2) }}</td><td>{{ number_format($audit->open_folio_balance,2) }}</td><td>{{ $audit->arrivals_count }}</td><td>{{ $audit->departures_count }}</td><td>O: {{ $audit->occupied_rooms }} / V: {{ $audit->vacant_rooms }} / OOS: {{ $audit->out_of_service_rooms }}</td><td><span class="hm-badge {{ $audit->status }}">{{ ucfirst($audit->status) }}</span></td>
                    <td class="hm-action-cell">
                        @if($audit->status !== 'closed')<form method="POST" action="{{ route('hotel-management.night-audit.close',$audit->id) }}" class="hm-inline-form">@csrf<button class="btn btn-xs hm-btn-action">Close</button></form>@endif
                        @if($audit->status === 'closed')<form method="POST" action="{{ route('hotel-management.night-audit.reopen',$audit->id) }}" class="hm-inline-form">@csrf<button class="btn btn-xs btn-warning">Reopen</button></form>@endif
                    </td>
                </tr>@empty<tr><td colspan="10"><div class="hm-empty">No Night Audit records found.</div></td></tr>@endforelse
            </tbody></table></div>
        </div>
    </div>

    @if($latest && $lines->count())
    <div class="box hm-card hm-box">
        <div class="box-header with-border hm-box-header"><h3 class="box-title">Latest Posted Audit Lines - {{ $latest->audit_no }}</h3></div>
        <div class="box-body"><div class="table-responsive"><table class="table table-bordered hm-table"><thead><tr><th>Section</th><th>Line</th><th>Reference</th><th class="text-right">Amount</th></tr></thead><tbody>@foreach($lines as $line)<tr><td>{{ $line->section }}</td><td>{{ $line->line_label }}</td><td>{{ $line->line_value }}</td><td class="text-right">{{ number_format($line->amount,2) }}</td></tr>@endforeach</tbody></table></div></div>
    </div>
    @endif
</section>
@endsection
