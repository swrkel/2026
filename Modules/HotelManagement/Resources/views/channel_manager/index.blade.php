@extends('layouts.app')
@section('title', 'Hotel Channel Manager')
@section('content')
<section class="content-header hm-page-header">
    <h1><i class="fa fa-cloud-upload"></i> Hotel Channel Manager <small>OTA channels, rate maps, availability and external bookings</small></h1>
</section>
<section class="content hm-pos-scope">
    @include('hotelmanagement::partials.nav')
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="row hm-kpi-row">
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Channels</div><div class="hm-kpi-value">{{ $channelManager['channels_count'] }}</div><div class="hm-kpi-sub">OTA / direct partners</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Active Channels</div><div class="hm-kpi-value">{{ $channelManager['active_channels'] }}</div><div class="hm-kpi-sub">Currently sellable</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Open Bookings</div><div class="hm-kpi-value">{{ $channelManager['open_bookings'] }}</div><div class="hm-kpi-sub">New / confirmed / modified</div></div></div>
        <div class="col-md-3 col-sm-6"><div class="hm-kpi"><div class="hm-kpi-label">Net Revenue</div><div class="hm-kpi-value">{{ number_format($channelManager['net_revenue'], 2) }}</div><div class="hm-kpi-sub">After channel commission</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Sales Channel</h3></div><div class="box-body">
                <form method="POST" action="{{ route('hotel-management.channel-manager.channel') }}">@csrf
                    <div class="form-group"><label>Channel Name</label><input name="channel_name" class="form-control" required placeholder="Booking.com / Agoda / Direct Web"></div>
                    <div class="hm-form-grid" style="grid-template-columns:repeat(2,minmax(120px,1fr))">
                        <div class="form-group"><label>Code</label><input name="channel_code" class="form-control" placeholder="Auto if blank"></div>
                        <div class="form-group"><label>Type</label><select name="channel_type" class="form-control"><option value="ota">OTA</option><option value="direct_web">Direct Web</option><option value="corporate">Corporate</option><option value="agent">Agent</option></select></div>
                        <div class="form-group"><label>Email</label><input type="email" name="contact_email" class="form-control"></div>
                        <div class="form-group"><label>Commission %</label><input type="number" step="0.01" name="commission_percent" class="form-control" value="0"></div>
                        <div class="form-group"><label>Active</label><select name="is_active" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
                    </div>
                    <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
                    <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Channel</button></div>
                </form>
            </div></div>
        </div>
        <div class="col-md-8">
            <div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Rate Mapping</h3></div><div class="box-body">
                <form method="POST" action="{{ route('hotel-management.channel-manager.rate-map') }}">@csrf
                    <div class="hm-form-grid" style="grid-template-columns:repeat(4,minmax(120px,1fr))">
                        <div class="form-group"><label>Channel</label><select name="channel_id" class="form-control" required><option value="">Select</option>@foreach($channelManager['channels'] as $channel)<option value="{{ $channel->id }}">{{ $channel->channel_name }}</option>@endforeach</select></div>
                        <div class="form-group"><label>Room Type ID</label><input type="number" name="room_type_id" class="form-control" placeholder="Optional"></div>
                        <div class="form-group"><label>Rate Plan ID</label><input type="number" name="rate_plan_id" class="form-control" placeholder="Optional"></div>
                        <div class="form-group"><label>Sell Rate</label><input type="number" step="0.01" name="sell_rate" class="form-control" required></div>
                        <div class="form-group"><label>External Room Code</label><input name="external_room_code" class="form-control"></div>
                        <div class="form-group"><label>External Rate Code</label><input name="external_rate_code" class="form-control"></div>
                        <div class="form-group"><label>Currency</label><input name="currency" class="form-control" value="LKR"></div>
                        <div class="form-group"><label>Active</label><select name="is_active" class="form-control"><option value="1">Yes</option><option value="0">No</option></select></div>
                    </div>
                    <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-link"></i> Save Rate Map</button></div>
                </form>
            </div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Availability / Stop Sell</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.channel-manager.availability') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(3,minmax(120px,1fr))">
                    <div class="form-group"><label>Channel</label><select name="channel_id" class="form-control" required><option value="">Select</option>@foreach($channelManager['channels'] as $channel)<option value="{{ $channel->id }}">{{ $channel->channel_name }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Room Type ID</label><input type="number" name="room_type_id" class="form-control" placeholder="Optional"></div>
                    <div class="form-group"><label>Date</label><input type="date" name="available_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                    <div class="form-group"><label>Available Rooms</label><input type="number" name="available_rooms" class="form-control" value="0" required></div>
                    <div class="form-group"><label>Stop Sell</label><select name="stop_sell" class="form-control"><option value="0">No</option><option value="1">Yes</option></select></div>
                    <div class="form-group"><label>Min Stay</label><input type="number" name="min_stay" class="form-control" value="0"></div>
                </div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-calendar-check-o"></i> Save Availability</button></div>
            </form>
        </div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">External Booking Capture</h3></div><div class="box-body">
            <form method="POST" action="{{ route('hotel-management.channel-manager.booking') }}">@csrf
                <div class="hm-form-grid" style="grid-template-columns:repeat(3,minmax(120px,1fr))">
                    <div class="form-group"><label>Channel</label><select name="channel_id" class="form-control" required><option value="">Select</option>@foreach($channelManager['channels'] as $channel)<option value="{{ $channel->id }}">{{ $channel->channel_name }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Booking Ref</label><input name="external_booking_ref" class="form-control" required></div>
                    <div class="form-group"><label>Guest Name</label><input name="guest_name" class="form-control" required></div>
                    <div class="form-group"><label>Mobile</label><input name="guest_mobile" class="form-control"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="guest_email" class="form-control"></div>
                    <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="new">New</option><option value="confirmed">Confirmed</option><option value="modified">Modified</option><option value="cancelled">Cancelled</option></select></div>
                    <div class="form-group"><label>Arrival</label><input type="date" name="arrival_date" class="form-control" required></div>
                    <div class="form-group"><label>Departure</label><input type="date" name="departure_date" class="form-control" required></div>
                    <div class="form-group"><label>Rooms</label><input type="number" name="rooms" class="form-control" value="1"></div>
                    <div class="form-group"><label>Gross</label><input type="number" step="0.01" name="gross_amount" class="form-control" value="0"></div>
                    <div class="form-group"><label>Commission</label><input type="number" step="0.01" name="commission_amount" class="form-control" value="0"></div>
                </div>
                <div class="text-right"><button class="btn hm-btn-add"><i class="fa fa-plus"></i> Save Booking</button></div>
            </form>
        </div></div></div>
    </div>

    <div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Channel Booking Register</h3></div><div class="box-body">
        <div class="hm-toolbar"><input class="form-control hm-search-input" placeholder="Search bookings" style="max-width:260px"><button class="btn hm-btn-csv">CSV</button><button class="btn hm-btn-excel">Excel</button><button class="btn hm-btn-pdf">PDF</button><button class="btn hm-btn-print">Print</button><button class="btn hm-btn-col">Column Visibility</button></div>
        <div class="table-responsive"><table class="table hm-table"><thead><tr><th>ID</th><th>Channel</th><th>Ref</th><th>Guest</th><th>Arrival</th><th>Departure</th><th>Rooms</th><th>Gross</th><th>Commission</th><th>Net</th><th>Status</th><th>Action</th></tr></thead><tbody>
            @forelse($channelManager['bookings'] as $row)
                <tr><td>{{ $row->id }}</td><td>{{ $row->channel_name ?? '-' }}</td><td>{{ $row->external_booking_ref }}</td><td>{{ $row->guest_name }}</td><td>{{ $row->arrival_date }}</td><td>{{ $row->departure_date }}</td><td>{{ $row->rooms }}</td><td>{{ number_format($row->gross_amount,2) }}</td><td>{{ number_format($row->commission_amount,2) }}</td><td>{{ number_format($row->net_amount,2) }}</td><td><span class="hm-badge {{ $row->status }}">{{ $row->status }}</span></td><td><form method="POST" action="{{ route('hotel-management.channel-manager.booking-status', $row->id) }}" class="form-inline">@csrf<select name="status" class="form-control input-sm"><option value="confirmed">Confirmed</option><option value="converted">Converted</option><option value="cancelled">Cancelled</option><option value="no_show">No Show</option></select><button class="btn btn-xs hm-btn-edit">Update</button></form></td></tr>
            @empty
                <tr><td colspan="12"><div class="hm-empty">No channel bookings found yet.</div></td></tr>
            @endforelse
        </tbody></table></div>
    </div></div>

    <div class="row">
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Rate Maps</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>Channel</th><th>Room Type</th><th>Rate Plan</th><th>External Codes</th><th>Rate</th><th>Active</th></tr></thead><tbody>@forelse($channelManager['rate_maps'] as $map)<tr><td>{{ $map->channel_name ?? '-' }}</td><td>{{ $map->room_type_id ?? '-' }}</td><td>{{ $map->rate_plan_id ?? '-' }}</td><td>{{ $map->external_room_code }} / {{ $map->external_rate_code }}</td><td>{{ $map->currency }} {{ number_format($map->sell_rate,2) }}</td><td>{{ $map->is_active ? 'Yes' : 'No' }}</td></tr>@empty<tr><td colspan="6"><div class="hm-empty">No rate maps saved.</div></td></tr>@endforelse</tbody></table></div></div></div></div>
        <div class="col-md-6"><div class="box hm-card"><div class="box-header with-border"><h3 class="box-title">Availability Snapshot</h3></div><div class="box-body"><div class="table-responsive"><table class="table hm-table"><thead><tr><th>Channel</th><th>Date</th><th>Room Type</th><th>Available</th><th>Stop Sell</th><th>Min Stay</th></tr></thead><tbody>@forelse($channelManager['availability'] as $a)<tr><td>{{ $a->channel_name ?? '-' }}</td><td>{{ $a->available_date }}</td><td>{{ $a->room_type_id ?? '-' }}</td><td>{{ $a->available_rooms }}</td><td>{{ $a->stop_sell ? 'Yes' : 'No' }}</td><td>{{ $a->min_stay }}</td></tr>@empty<tr><td colspan="6"><div class="hm-empty">No availability records saved.</div></td></tr>@endforelse</tbody></table></div><ul>@foreach($channelManager['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div></div></div>
    </div>
</section>
@endsection
