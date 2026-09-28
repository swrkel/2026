@extends('managementreport::layouts.master', [
    'pageTitle' => 'Delivery History',
    'pageSubtitle' => 'Audit SMS, email, WhatsApp and public report links.'
])

@section('page_actions')
<a href="{{ route('managementreport.dashboard') }}" class="mgmt-btn mgmt-btn-default"><i class="fa fa-dashboard"></i> Dashboard</a>
<a href="{{ route('managementreport.daily.index') }}" class="mgmt-btn mgmt-btn-primary"><i class="fa fa-plus"></i> New Report</a>
<a href="{{ route('managementreport.saved.index') }}" class="mgmt-btn mgmt-btn-success"><i class="fa fa-archive"></i> Saved Reports</a>
@endsection

@section('managementreport_content')
<div class="mgmt-panel">
    <form class="mgmt-inline-filter" method="GET">
        <div class="mgmt-field">
            <label>Channel</label>
            <select name="channel" class="mgmt-native-all-filter" data-default-all="1">
                <option value="__all__" {{ in_array((string) request('channel', '__all__'), ['', '__all__'], true) ? 'selected' : '' }}>All</option>
                @foreach(['sms','email','whatsapp'] as $channel)
                    <option value="{{ $channel }}" {{ request('channel') === $channel ? 'selected' : '' }}>{{ ucfirst($channel) }}</option>
                @endforeach
            </select>
        </div>
        <div class="mgmt-field">
            <label>Status</label>
            <select name="status" class="mgmt-native-all-filter" data-default-all="1">
                <option value="__all__" {{ in_array((string) request('status', '__all__'), ['', '__all__'], true) ? 'selected' : '' }}>All</option>
                @foreach(['pending','queued','sent','ready','failed','revoked'] as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <button class="mgmt-btn mgmt-btn-primary" type="submit">Filter</button>
        <a class="mgmt-btn" href="{{ route('managementreport.shares.index') }}">Reset</a>
    </form>

    <div class="table-responsive">
        <table class="table mgmt-table">
            <thead><tr><th>Date</th><th>Report</th><th>Channel</th><th>Recipients</th><th>Status</th><th>Views</th><th>Expiry</th><th class="text-right">Action</th></tr></thead>
            <tbody>
            @forelse($shares as $share)
                <tr>
                    <td>{{ $share->created_at->format('d M Y, h:i A') }}</td>
                    <td>@if($share->run)<a href="{{ route('managementreport.saved.show', $share->run) }}">{{ $share->run->report_title }}</a>@else Deleted report @endif</td>
                    <td><span class="mgmt-channel mgmt-channel-{{ $share->channel }}">{{ strtoupper($share->channel) }}</span></td>
                    <td>{{ $share->recipients->pluck('recipient')->implode(', ') }}</td>
                    <td><span class="mgmt-status mgmt-status-{{ $share->status }}">{{ ucfirst($share->status) }}</span>@if($share->failure_reason)<small class="mgmt-error">{{ $share->failure_reason }}</small>@endif</td>
                    <td>{{ number_format($share->view_count) }}</td>
                    <td>{{ optional($share->expires_at)->format('d M Y, h:i A') ?: 'No expiry' }}</td>
                    <td class="text-right">
                        @if(!$share->revoked_at)
                        <form method="POST" action="{{ route('managementreport.shares.revoke', $share) }}" onsubmit="return confirm('Revoke this public link?')">@csrf<button class="mgmt-btn mgmt-btn-sm mgmt-btn-danger" type="submit">Revoke</button></form>
                        @else <span class="mgmt-muted">Revoked</span> @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="mgmt-empty">No report delivery records.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mgmt-pagination">{{ $shares->links() }}</div>
</div>
@endsection
