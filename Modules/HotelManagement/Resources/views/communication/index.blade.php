@extends('hotelmanagement::layouts.app')

@section('hotel_content')
<section class="content-header">
    <h1>Guest Communication <small>Hotel SMS / notification bridge</small></h1>
</section>
<section class="content">
    @include('hotelmanagement::partials.nav')
    @if(session('status')) <div class="alert alert-success hm-alert">{{ session('status') }}</div> @endif

    <div class="row">
        <div class="col-md-2 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Templates</div><div class="hm-kpi-value">{{ $communication['templates'] }}</div><div class="hm-kpi-sub">Hotel events</div></div></div>
        <div class="col-md-2 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Queued</div><div class="hm-kpi-value">{{ $communication['pending'] }}</div><div class="hm-kpi-sub">Pending bridge</div></div></div>
        <div class="col-md-2 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Sent</div><div class="hm-kpi-value">{{ $communication['sent'] }}</div><div class="hm-kpi-sub">Completed</div></div></div>
        <div class="col-md-2 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Failed</div><div class="hm-kpi-value">{{ $communication['failed'] }}</div><div class="hm-kpi-sub">Need review</div></div></div>
        <div class="col-md-4 col-sm-12"><div class="hm-kpi"><div class="hm-kpi-label">Design Standard</div><div class="hm-kpi-value" style="font-size:20px">POS Style</div><div class="hm-kpi-sub">Same clean ERP module UI</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Create Message Template</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.communication.template') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(160px,1fr))">
                            <div class="form-group"><label>Template Code</label><input name="code" class="form-control" placeholder="booking_confirm"></div>
                            <div class="form-group"><label>Name</label><input name="name" class="form-control" required placeholder="Booking Confirmation"></div>
                            <div class="form-group"><label>Channel</label><select name="channel" class="form-control"><option value="sms">SMS</option><option value="email">Email</option><option value="whatsapp">WhatsApp</option></select></div>
                            <div class="form-group"><label>Hotel Event</label><select name="event_key" class="form-control"><option value="reservation_created">Reservation Created</option><option value="pre_arrival">Pre-arrival Reminder</option><option value="check_in">Check-in Welcome</option><option value="room_service">Room Service Update</option><option value="check_out">Check-out Thank You</option></select></div>
                        </div>
                        <div class="form-group" style="margin-top:12px"><label>Message</label><textarea name="message_body" class="form-control" rows="4" required placeholder="Dear {guest_name}, your reservation {reservation_no} is confirmed."></textarea></div>
                        <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Template</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box hm-card">
                <div class="box-header with-border"><h3 class="box-title">Queue Manual Guest Message</h3></div>
                <div class="box-body">
                    <form method="POST" action="{{ route('hotel-management.communication.queue') }}">
                        @csrf
                        <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(160px,1fr))">
                            <div class="form-group"><label>Recipient</label><input name="recipient" class="form-control" required placeholder="Mobile / email"></div>
                            <div class="form-group"><label>Channel</label><select name="channel" class="form-control"><option value="sms">SMS</option><option value="email">Email</option><option value="whatsapp">WhatsApp</option></select></div>
                            <div class="form-group"><label>Guest ID</label><input name="guest_id" type="number" class="form-control"></div>
                            <div class="form-group"><label>Reservation ID</label><input name="reservation_id" type="number" class="form-control"></div>
                        </div>
                        <div class="form-group" style="margin-top:12px"><label>Subject</label><input name="subject" class="form-control"></div>
                        <div class="form-group"><label>Message</label><textarea name="message_body" class="form-control" rows="4" required></textarea></div>
                        <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-paper-plane"></i> Queue Message</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="box hm-card">
        <div class="box-header with-border"><h3 class="box-title">Recent Hotel Message Logs</h3></div>
        <div class="box-body">
            <div class="hm-toolbar"><input class="form-control hm-search-input" placeholder="Search logs" style="max-width:260px"><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div>
            <div class="table-responsive"><table class="table hm-table"><thead><tr><th>ID</th><th>Channel</th><th>Recipient</th><th>Status</th><th>Source</th><th>Message</th><th>Date</th></tr></thead><tbody>
                @forelse($communication['recent'] as $log)
                    <tr><td>{{ $log->id ?? '' }}</td><td>{{ strtoupper($log->channel ?? '') }}</td><td>{{ $log->recipient ?? '' }}</td><td><span class="hm-badge {{ $log->status ?? '' }}">{{ $log->status ?? '' }}</span></td><td>{{ $log->source ?? '' }}</td><td>{{ \Illuminate\Support\Str::limit($log->message_body ?? '', 80) }}</td><td>{{ $log->created_at ?? '' }}</td></tr>
                @empty
                    <tr><td colspan="7"><div class="hm-empty">No communication logs found yet.</div></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="box hm-card"><div class="box-body"><ul>@foreach($communication['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div></div>
</section>
@endsection
