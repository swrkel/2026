@extends('layouts.app')
@section('title', 'Pumper Login Block / Unblock History')
@section('content')
<style>
    .login-audit-page .audit-toolbar { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:15px; flex-wrap:wrap; }
    .login-audit-page .audit-cards { display:grid; grid-template-columns:repeat(4,minmax(150px,1fr)); gap:12px; margin-bottom:18px; }
    .login-audit-page .audit-card { background:#fff; border:1px solid #e7edf3; border-radius:8px; padding:14px 16px; box-shadow:0 2px 8px rgba(31,45,61,.06); }
    .login-audit-page .audit-card small { color:#6c757d; display:block; margin-bottom:5px; }
    .login-audit-page .audit-card strong { font-size:22px; color:#253858; }
    .login-audit-page .audit-panel { background:#fff; border:1px solid #e7edf3; border-radius:8px; margin-bottom:18px; overflow:hidden; }
    .login-audit-page .audit-panel-title { padding:12px 15px; background:#f7f9fc; border-bottom:1px solid #e7edf3; font-weight:700; }
    .login-audit-page .table-responsive { margin:0; }
    .login-audit-page th { white-space:normal; vertical-align:middle !important; }
    .login-audit-page td { vertical-align:middle !important; }
    @media(max-width:900px){ .login-audit-page .audit-cards { grid-template-columns:repeat(2,minmax(140px,1fr)); } }
</style>
<section class="content-header main-content-inner login-audit-page">
    <div class="audit-toolbar">
        <div>
            <h3 style="margin:0 0 4px;">Pumper Login Block / Unblock History</h3>
            <span class="text-muted">Login access is tracked separately for each business and IP address. An invalid passcode that matches no user is shown as “Unknown / invalid passcode”.</span>
        </div>
        <div>
            <a href="{{ route('petrogeneral.blockedPumperLoginAttempt') }}" class="btn btn-warning"><i class="fa fa-ban"></i> Currently Blocked</a>
            <a href="{{ route('petrogeneral.pumper_management.index') }}" class="btn btn-primary"><i class="fa fa-users"></i> Pumper Management</a>
        </div>
    </div>

    <div class="audit-cards">
        <div class="audit-card"><small>Login Access Records</small><strong>{{ $historySummary['login_records'] }}</strong></div>
        <div class="audit-card"><small>Currently Blocked</small><strong>{{ $historySummary['currently_blocked'] }}</strong></div>
        <div class="audit-card"><small>Block Events</small><strong>{{ $historySummary['block_events'] }}</strong></div>
        <div class="audit-card"><small>Unblocked Events</small><strong>{{ $historySummary['unblocked_events'] }}</strong></div>
    </div>

    @if (!$historyReady)
        <div class="alert alert-warning">The history table is not installed yet. Run the supplied migration or SQL file. Existing login access records are shown below.</div>
    @endif

    <div class="audit-panel">
        <div class="audit-panel-title">Block / Unblock Incidents</div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" style="margin:0;">
                <thead><tr><th>Operator</th><th>Company No.</th><th>IP Address</th><th>Passcode</th><th>Attempts</th><th>Blocked Date &amp; Time</th><th>Unblocked Date &amp; Time</th><th>Unblocked By</th><th>Status</th><th>Source</th></tr></thead>
                <tbody>
                @forelse ($pumperLoginHistories as $history)
                    <tr>
                        <td>{{ $history->operator_name ?: ($history->pump_operator_id ? 'Operator #' . $history->pump_operator_id : 'Unknown / invalid passcode') }}</td>
                        <td>{{ $history->company_number ?: '-' }}</td>
                        <td>{{ $history->ip_address ?: '-' }}</td>
                        <td>{{ $history->passcode_mask ?: '-' }}</td>
                        <td>{{ $history->attempt_count }}</td>
                        <td>{{ optional($history->blocked_at)->format('Y-m-d H:i:s') ?: '-' }}</td>
                        <td>{{ optional($history->unblocked_at)->format('Y-m-d H:i:s') ?: '-' }}</td>
                        <td>{{ $history->unblocked_by_name ?: '-' }}</td>
                        <td><span class="label {{ $history->unblocked_at ? 'label-success' : 'label-danger' }}">{{ $history->unblocked_at ? 'Unblocked' : 'Blocked' }}</span></td>
                        <td>{{ $history->source_module ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center">No block/unblock incidents recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($historyReady)
            <div style="padding:10px 15px;">{{ $pumperLoginHistories->withQueryString()->links() }}</div>
        @endif
    </div>

    <div class="audit-panel">
        <div class="audit-panel-title">All Login Access Records — includes active/unblocked records retained by the old system</div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" style="margin:0;">
                <thead><tr><th>Company No.</th><th>IP Address</th><th>Last Passcode</th><th>Attempts</th><th>Status</th><th>Last Updated</th><th>Action</th></tr></thead>
                <tbody>
                @forelse ($pumperLoginAttempts as $attempt)
                    @php($rawPasscode = trim((string) $attempt->last_entered_passcode))
                    <tr>
                        <td>{{ $attempt->company_number ?: '-' }}</td>
                        <td>{{ $attempt->ip_address ?: '-' }}</td>
                        <td>{{ $rawPasscode === '' ? '-' : str_repeat('*', max(2, strlen($rawPasscode) - 2)) . substr($rawPasscode, -2) }}</td>
                        <td>{{ $attempt->attempt_count }}</td>
                        <td><span class="label {{ $attempt->status === 'Blocked' ? 'label-danger' : 'label-success' }}">{{ $attempt->status }}</span></td>
                        <td>{{ optional($attempt->updated_at)->format('Y-m-d H:i:s') ?: optional($attempt->created_at)->format('Y-m-d H:i:s') }}</td>
                        <td>@if ($attempt->status === 'Blocked')<a href="{{ route('petrogeneral.unblockPumperLoginAttempt', ['id' => $attempt->id, 'return_to' => 'history']) }}" class="btn btn-primary btn-xs">Unblock</a>@else<span class="text-muted">Already active</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No login access records found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
