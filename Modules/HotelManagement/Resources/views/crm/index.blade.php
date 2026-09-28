@extends('hotelmanagement::layouts.app')
@section('hotel_content')
<section class="content-header hm-page-heading">
    <h1>Guest CRM <small>Guest profiles, preferences, notes and stay history</small></h1>
</section>
<section class="content hm-content-wrap">
@include('hotelmanagement::partials.nav')
@if(session('status'))<div class="alert alert-success hm-alert">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger hm-alert"><strong>Please check:</strong> {{ $errors->first() }}</div>@endif

<div class="box hm-card">
    <div class="box-header hm-card-header"><h3 class="box-title">Add Guest Profile</h3></div>
    <div class="box-body">
        <form method="POST" action="{{ route('hotel-management.crm.store') }}">@csrf
            <div class="hm-form-grid hm-form-grid-4">
                <div class="form-group"><label>Guest Code</label><input name="guest_code" class="form-control" placeholder="Auto if blank"></div>
                <div class="form-group"><label>Guest Name</label><input name="guest_name" class="form-control" required></div>
                <div class="form-group"><label>Mobile</label><input name="mobile" class="form-control"></div>
                <div class="form-group"><label>Email</label><input name="email" type="email" class="form-control"></div>
                <div class="form-group"><label>ID / Passport</label><input name="id_no" class="form-control"></div>
                <div class="form-group"><label>Nationality</label><input name="nationality" class="form-control"></div>
                <div class="form-group"><label>Date of Birth</label><input name="date_of_birth" type="date" class="form-control"></div>
                <div class="form-group"><label>Gender</label><select name="gender" class="form-control"><option value="">Please Select</option><option>Male</option><option>Female</option><option>Other</option></select></div>
                <div class="form-group"><label>VIP Level</label><select name="vip_level" class="form-control"><option value="">Normal</option><option>Silver</option><option>Gold</option><option>Platinum</option><option>Black</option></select></div>
                <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option><option value="blacklisted">Blacklisted</option></select></div>
                <div class="form-group"><label>Marketing Consent</label><select name="marketing_consent" class="form-control"><option value="0">No</option><option value="1">Yes</option></select></div>
                <div class="form-group hm-span-2"><label>Address</label><input name="address" class="form-control"></div>
            </div>
            <br><button class="btn hm-btn-add"><i class="fa fa-save"></i> Save Guest</button>
        </form>
    </div>
</div>

<div class="box hm-card">
    <div class="box-header hm-card-header"><h3 class="box-title">Guest Search</h3></div>
    <div class="box-body">
        <form method="GET" action="{{ route('hotel-management.crm.index') }}" class="hm-toolbar hm-filter-bar">
            <input name="q" value="{{ $search ?? '' }}" class="form-control hm-search-input" placeholder="Search code, name, mobile, email, passport">
            <select name="status" class="form-control hm-date-input"><option value="">All Status</option><option value="active" {{ ($status ?? '')==='active' ? 'selected' : '' }}>Active</option><option value="inactive" {{ ($status ?? '')==='inactive' ? 'selected' : '' }}>Inactive</option><option value="blacklisted" {{ ($status ?? '')==='blacklisted' ? 'selected' : '' }}>Blacklisted</option></select>
            <button class="btn hm-btn-search"><i class="fa fa-search"></i> Search</button>
            <a href="{{ route('hotel-management.crm.index') }}" class="btn hm-btn-light">Reset</a>
        </form>
    </div>
</div>

<div class="box hm-card">
    <div class="box-header hm-card-header"><h3 class="box-title">Guest List</h3></div>
    <div class="box-body">
        @include('hotelmanagement::partials.toolbar')
        <div class="table-responsive"><table class="table hm-table table-striped">
            <thead><tr><th>Code</th><th>Guest</th><th>Contact</th><th>VIP</th><th>Stays</th><th>Last Stay</th><th>Value</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($guests as $row)
                @php($stat = $stayStats[$row->id] ?? null)
                <tr>
                    <td>{{ $row->guest_code ?? '' }}</td>
                    <td><strong>{{ $row->guest_name ?? '' }}</strong><br><small>{{ $row->id_no ?? '' }} {{ isset($row->nationality) ? ' | '.$row->nationality : '' }}</small></td>
                    <td>{{ $row->mobile ?? '' }}<br><small>{{ $row->email ?? '' }}</small></td>
                    <td><span class="hm-badge {{ strtolower($row->vip_level ?? 'normal') }}">{{ $row->vip_level ?? 'Normal' }}</span></td>
                    <td>{{ $stat->reservation_count ?? 0 }}</td>
                    <td>{{ $stat->last_stay_date ?? '-' }}</td>
                    <td>{{ number_format((float)($stat->lifetime_value ?? 0), 2) }}</td>
                    <td><span class="hm-badge {{ $row->status ?? 'active' }}">{{ ucfirst($row->status ?? 'active') }}</span></td>
                    <td><button type="button" class="btn btn-xs hm-btn-view" data-toggle="collapse" data-target="#guest-crm-{{ $row->id }}">Profile</button></td>
                </tr>
                <tr class="collapse" id="guest-crm-{{ $row->id }}"><td colspan="9">
                    <div class="hm-profile-panel">
                        <div class="hm-profile-col">
                            <h4>Edit Profile</h4>
                            <form method="POST" action="{{ route('hotel-management.crm.update', $row->id) }}">@csrf @method('PUT')
                                <div class="hm-form-grid hm-form-grid-3">
                                    <input name="guest_code" class="form-control" value="{{ $row->guest_code ?? '' }}" required>
                                    <input name="guest_name" class="form-control" value="{{ $row->guest_name ?? '' }}" required>
                                    <input name="mobile" class="form-control" value="{{ $row->mobile ?? '' }}">
                                    <input name="email" type="email" class="form-control" value="{{ $row->email ?? '' }}">
                                    <input name="id_no" class="form-control" value="{{ $row->id_no ?? '' }}">
                                    <input name="nationality" class="form-control" value="{{ $row->nationality ?? '' }}">
                                    <input name="date_of_birth" type="date" class="form-control" value="{{ $row->date_of_birth ?? '' }}">
                                    <select name="vip_level" class="form-control"><option value="">Normal</option>@foreach(['Silver','Gold','Platinum','Black'] as $vip)<option value="{{ $vip }}" {{ ($row->vip_level ?? '')===$vip ? 'selected' : '' }}>{{ $vip }}</option>@endforeach</select>
                                    <select name="status" class="form-control"><option value="active" {{ ($row->status ?? '')==='active' ? 'selected' : '' }}>Active</option><option value="inactive" {{ ($row->status ?? '')==='inactive' ? 'selected' : '' }}>Inactive</option><option value="blacklisted" {{ ($row->status ?? '')==='blacklisted' ? 'selected' : '' }}>Blacklisted</option></select>
                                    <input name="address" class="form-control hm-span-3" value="{{ $row->address ?? '' }}" placeholder="Address">
                                </div><br><button class="btn btn-sm hm-btn-add">Update</button>
                            </form>
                        </div>
                        <div class="hm-profile-col">
                            <h4>Preferences</h4>
                            <form method="POST" action="{{ route('hotel-management.crm.preferences.store', $row->id) }}" class="hm-inline-form">@csrf
                                <input name="preference_type" class="form-control" placeholder="Type" required>
                                <input name="preference_value" class="form-control" placeholder="Value">
                                <button class="btn btn-sm hm-btn-add">Add</button>
                            </form>
                            <ul class="hm-mini-list">@forelse(($preferences[$row->id] ?? collect()) as $pref)<li><strong>{{ $pref->preference_type }}</strong>: {{ $pref->preference_value }}<form method="POST" action="{{ route('hotel-management.crm.preferences.destroy', [$row->id, $pref->id]) }}">@csrf @method('DELETE')<button class="btn-link text-danger">remove</button></form></li>@empty<li>No preferences recorded.</li>@endforelse</ul>
                            <h4>Notes</h4>
                            <form method="POST" action="{{ route('hotel-management.crm.notes.store', $row->id) }}">@csrf
                                <div class="hm-inline-form"><select name="note_type" class="form-control"><option>general</option><option>complaint</option><option>preference</option><option>warning</option></select><input name="note" class="form-control" placeholder="Note" required><button class="btn btn-sm hm-btn-add">Add</button></div>
                            </form>
                            <ul class="hm-mini-list">@forelse(($notes[$row->id] ?? collect()) as $note)<li><span class="hm-badge">{{ $note->note_type }}</span> {{ $note->note }}<form method="POST" action="{{ route('hotel-management.crm.notes.destroy', [$row->id, $note->id]) }}">@csrf @method('DELETE')<button class="btn-link text-danger">remove</button></form></li>@empty<li>No notes recorded.</li>@endforelse</ul>
                        </div>
                    </div>
                </td></tr>
            @empty<tr><td colspan="9"><div class="hm-empty">No records found.</div></td></tr>@endforelse
            </tbody>
        </table></div>
    </div>
</div>
</section>
@endsection
