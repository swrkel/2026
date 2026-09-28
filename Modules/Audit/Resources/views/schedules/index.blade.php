@extends('audit::layout')
@section('audit-title','Audit Schedules')
@section('audit-content')
@php
    $enabledCount = $schedules->where('is_enabled', true)->count();
    $lastRun = $schedules->pluck('last_run_at')->filter()->sortDesc()->first();
@endphp

<div class="audit-cards audit-schedule-stats">
    <div class="audit-stat latest">
        <span>Total Schedules</span>
        <strong>{{ $schedules->count() }}</strong>
    </div>
    <div class="audit-stat resolved">
        <span>Enabled Schedules</span>
        <strong>{{ $enabledCount }}</strong>
    </div>
    <div class="audit-stat {{ $schedulerEnabled ? 'resolved' : 'warning' }}">
        <span>Automatic Runner</span>
        <strong class="audit-status-word">{{ $schedulerEnabled ? 'Enabled' : 'Disabled' }}</strong>
    </div>
    <div class="audit-stat">
        <span>Last Scheduled Run</span>
        <strong class="audit-stat-date">{{ $lastRun ? \Carbon\Carbon::parse($lastRun)->format('d M Y H:i') : 'Never' }}</strong>
    </div>
</div>

<div class="audit-grid-two audit-schedule-grid">
    <div class="audit-card audit-schedule-create-card">
        <div class="audit-card-title">Create Audit Schedule</div>
        <div class="audit-note audit-card-intro">Choose when Audit should run automatically for the current business and which areas it should check.</div>

        <form method="post" action="{{ route('audit.schedules.store') }}" class="audit-schedule-form">
            @csrf

            <label class="audit-field-label" for="audit_schedule_name">Schedule Name</label>
            <input id="audit_schedule_name" class="audit-input full" name="name" value="{{ old('name') }}" placeholder="Example: Daily full audit" required>

            <div class="audit-schedule-form-row">
                <div>
                    <label class="audit-field-label" for="audit_schedule_frequency">Frequency</label>
                    <select id="audit_schedule_frequency" class="audit-input full audit-schedule-frequency" name="frequency">
                        @foreach(['hourly' => 'Hourly', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $value => $label)
                            <option value="{{ $value }}" {{ old('frequency', 'daily') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="audit-schedule-time-wrap">
                    <label class="audit-field-label" for="audit_schedule_time">Preferred Run Time</label>
                    <input id="audit_schedule_time" class="audit-input full" type="time" name="run_time" value="{{ old('run_time') }}">
                </div>
            </div>

            <div class="audit-schedule-frequency-help audit-note" data-schedule-help></div>

            <div class="audit-field-label audit-module-label">Modules to Audit</div>
            <div class="audit-check-grid audit-schedule-module-grid">
                @foreach($modules as $m)
                    <label>
                        <input type="checkbox" name="modules[]" value="{{ $m }}" {{ in_array($m, old('modules', $modules), true) ? 'checked' : '' }}>
                        <span>{{ $m }}</span>
                    </label>
                @endforeach
            </div>

            <input type="hidden" name="is_enabled" value="1">
            <button class="audit-btn primary audit-create-schedule-btn">Create Schedule</button>
        </form>
    </div>

    <div class="audit-card audit-scheduler-info-card">
        <div class="audit-card-title">Automatic Scheduler</div>

        <div class="audit-scheduler-status {{ $schedulerEnabled ? 'enabled' : 'disabled' }}">
            <span class="audit-scheduler-dot"></span>
            <div>
                <strong>Audit automatic runner: {{ $schedulerEnabled ? 'Enabled' : 'Disabled' }}</strong>
                <div>{{ $schedulerEnabled ? 'The Audit module is registered with Laravel Scheduler.' : 'Automatic schedules will not run until the Audit scheduler is enabled.' }}</div>
            </div>
        </div>

        <div class="audit-info-block">
            <strong>Server requirement</strong>
            <p>The server must run Laravel Scheduler normally. The standard server cron is:</p>
            <code>* * * * * cd /home/nivasa/public_html &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code>
        </div>

        <div class="audit-info-block">
            <strong>Audit tenant command</strong>
            <p>The module checks due schedules tenant-by-tenant with:</p>
            <code>php artisan audit:scheduled --all-tenants</code>
        </div>

        <div class="audit-info-block compact">
            <strong>Manual tenant test</strong>
            <code>php artisan audit:scheduled --tenant=&lt;tenant-id-or-domain&gt;</code>
        </div>

        <div class="audit-note audit-scheduler-note">
            Audit schedules are read-only against operational ERP records. Scheduled runs write only to the standalone <code>audit_*</code> tables.
        </div>
    </div>
</div>

<div class="audit-card audit-schedule-list-card">
    <div class="audit-schedule-list-heading">
        <div>
            <div class="audit-card-title">Saved Schedules</div>
            <div class="audit-note">Only schedules belonging to the current business are shown here.</div>
        </div>
    </div>

    <div class="audit-table-wrap audit-schedule-table-wrap">
        <table class="audit-table audit-schedule-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Frequency</th>
                    <th>Run Time</th>
                    <th>Modules</th>
                    <th>Last Run</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedules as $s)
                    <tr>
                        <td data-label="Name"><strong>{{ $s->name }}</strong></td>
                        <td data-label="Frequency"><span class="audit-badge information">{{ ucfirst($s->frequency) }}</span></td>
                        <td data-label="Run Time">{{ $s->frequency === 'hourly' ? 'Every hour' : ($s->run_time ? substr($s->run_time, 0, 5) : 'After due check') }}</td>
                        <td data-label="Modules" class="audit-schedule-modules-cell">{{ implode(', ', $s->modules ?: []) }}</td>
                        <td data-label="Last Run">{{ optional($s->last_run_at)->format('d M Y H:i') ?: 'Never' }}</td>
                        <td data-label="Status">
                            <span class="audit-badge {{ $s->is_enabled ? 'completed' : 'failed' }}">{{ $s->is_enabled ? 'Enabled' : 'Disabled' }}</span>
                        </td>
                        <td data-label="Action">
                            <form method="post" action="{{ route('audit.schedules.toggle',$s) }}">
                                @csrf
                                <button class="audit-btn small">{{ $s->is_enabled ? 'Disable' : 'Enable' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr class="audit-schedule-empty-row"><td colspan="7">No schedules have been created for this business yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
