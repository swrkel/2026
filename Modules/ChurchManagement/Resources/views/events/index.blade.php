@extends('churchmanagement::layouts.app', [
    'title' => __('churchmanagement::lang.events'),
    'heading' => __('churchmanagement::lang.events'),
    'subheading' => 'The church calendar. Anything that is not a regular service.',
])

@section('chc_content')

@if(! $installed)
    @include('churchmanagement::partials.install_notice')
@endif

<div class="ch-kpi-grid ch-standard-grid" style="grid-template-columns:repeat(2,minmax(180px,1fr))">
    <div class="ch-kpi">
        <div class="ch-kpi-top">
            <div class="ch-icon"><i class="fa fa-calendar"></i></div>
            <div class="label-text">Upcoming</div>
        </div>
        <div class="value">{{ number_format($counts['upcoming']) }}</div>
        <div class="hint">Planned or confirmed</div>
        <div class="spark"></div>
    </div>
    <div class="ch-kpi success">
        <div class="ch-kpi-top">
            <div class="ch-icon"><i class="fa fa-calendar-check-o"></i></div>
            <div class="label-text">This Month</div>
        </div>
        <div class="value">{{ number_format($counts['this_month']) }}</div>
        <div class="hint">{{ now()->format('F Y') }}</div>
        <div class="spark"></div>
    </div>
</div>

<div class="ch-card" id="chc-event-form-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-plus text-primary"></i>
                <span id="chc-form-title">Add an Event</span></h3>
            <div class="ch-card-subtitle">
                Times are optional, so a whole-day or multi-day event needs no invented ones.
            </div>
        </div>
        <button type="button" class="btn btn-default btn-sm" id="chc-cancel-edit" style="display:none">
            <i class="fa fa-times"></i> {{ __('churchmanagement::lang.cancel') }}</button>
    </div>
    <div class="ch-card-body">
        <form method="post" id="chc-event-form" action="{{ route('churchmanagement.events.store') }}">
            @csrf
            <input type="hidden" name="_method" id="chc-form-method" value="POST">

            <div class="chc-form-grid">
                <div class="chc-field">
                    <label>Title <span style="color:#dc2626">*</span></label>
                    <input type="text" name="title" id="chc-title" maxlength="191" required value="{{ old('title') }}">
                </div>
                <div class="chc-field">
                    <label>Type</label>
                    <input type="text" name="event_type" id="chc-event_type" maxlength="50"
                           value="{{ old('event_type') }}" placeholder="Wedding, Baptism, Outreach…">
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.membership_status') === '' ? '' : 'Status' }}</label>
                    <select name="status" id="chc-status">
                        @foreach($statuses as $chcKey => $chcLabel)
                            <option value="{{ $chcKey }}" @selected(old('status', 'planned') === $chcKey)>{{ $chcLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="chc-field">
                    <label>Date <span style="color:#dc2626">*</span></label>
                    <input type="date" name="event_date" id="chc-event_date" required
                           value="{{ old('event_date', now()->format('Y-m-d')) }}">
                </div>
                <div class="chc-field">
                    <label>End Date</label>
                    <input type="date" name="end_date" id="chc-end_date" value="{{ old('end_date') }}">
                    <div class="chc-hint">Only for an event spanning more than one day.</div>
                </div>
                <div class="chc-field">
                    <label>Venue</label>
                    <input type="text" name="venue" id="chc-venue" maxlength="191" value="{{ old('venue') }}">
                </div>

                <div class="chc-field">
                    <label>Start Time</label>
                    <input type="time" name="start_time" id="chc-start_time" value="{{ old('start_time') }}">
                </div>
                <div class="chc-field">
                    <label>End Time</label>
                    <input type="time" name="end_time" id="chc-end_time" value="{{ old('end_time') }}">
                </div>
                <div class="chc-field">
                    <label>Organiser</label>
                    <select name="organiser_member_id" id="chc-organiser_member_id">
                        <option value="">— Not set —</option>
                        @foreach($members as $chcMemId => $chcMemName)
                            <option value="{{ $chcMemId }}" @selected((string) old('organiser_member_id') === (string) $chcMemId)>{{ $chcMemName }}</option>
                        @endforeach
                    </select>
                </div>

                @if($locations->count())
                    <div class="chc-field">
                        <label>Location</label>
                        <select name="business_location_id" id="chc-business_location_id">
                            <option value="">— Not set —</option>
                            @foreach($locations as $chcLocId => $chcLocName)
                                <option value="{{ $chcLocId }}" @selected((string) old('business_location_id') === (string) $chcLocId)>{{ $chcLocName }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="chc-field" style="grid-column:1 / -1">
                    <label>Description</label>
                    <textarea name="description" id="chc-description" maxlength="2000">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="chc-form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> <span id="chc-submit-label">{{ __('churchmanagement::lang.save') }}</span></button>
            </div>
        </form>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-list text-primary"></i> Calendar</h3>
            <div class="ch-card-subtitle">{{ $installed ? $events->total() : 0 }} event(s) shown.</div>
        </div>
    </div>
    <div class="ch-card-body">

        <form method="get" class="ch-toolbar">
            <div class="chc-filters" style="flex:1">
                <div class="chc-field">
                    <label>Show</label>
                    <select name="scope">
                        <option value="upcoming" @selected(($filters['scope'] ?? 'upcoming') === 'upcoming')>Upcoming</option>
                        <option value="past" @selected(($filters['scope'] ?? '') === 'past')>Past</option>
                        <option value="all" @selected(($filters['scope'] ?? '') === 'all')>All</option>
                    </select>
                </div>
                <div class="chc-field">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All</option>
                        @foreach($statuses as $chcKey => $chcLabel)
                            <option value="{{ $chcKey }}" @selected(($filters['status'] ?? '') === $chcKey)>{{ $chcLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.search') }}</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Title, venue or type">
                </div>
                <div class="chc-field" style="flex:0 0 auto;min-width:0">
                    <label>&nbsp;</label>
                    <div style="display:flex;gap:8px">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Apply</button>
                        <a href="{{ route('churchmanagement.events.index') }}" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Reset</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="chc-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event</th>
                        <th>Venue</th>
                        <th>Organiser</th>
                        <th>Status</th>
                        <th class="chc-actions-cell">{{ __('churchmanagement::lang.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($events as $chcRow)
                    <tr>
                        <td>
                            {{ \Carbon\Carbon::parse($chcRow->event_date)->format('d M Y') }}
                            @if($chcRow->end_date && $chcRow->end_date !== $chcRow->event_date)
                                <div class="ch-page-note">to {{ \Carbon\Carbon::parse($chcRow->end_date)->format('d M Y') }}</div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $chcRow->title }}</strong>
                            @if($chcRow->event_type)<div class="ch-page-note">{{ $chcRow->event_type }}</div>@endif
                            @if($chcRow->start_time)
                                <div class="ch-page-note">
                                    {{ \Carbon\Carbon::parse($chcRow->start_time)->format('H:i') }}
                                    @if($chcRow->end_time) – {{ \Carbon\Carbon::parse($chcRow->end_time)->format('H:i') }} @endif
                                </div>
                            @endif
                        </td>
                        <td>{{ $chcRow->venue ?: '—' }}</td>
                        <td>{{ $members[$chcRow->organiser_member_id] ?? '—' }}</td>
                        <td><span class="chc-pill {{ $chcRow->status === 'cancelled' ? 'departed' : ($chcRow->status === 'completed' ? 'member' : ($chcRow->status === 'confirmed' ? 'visitor' : 'inactive')) }}">
                            {{ $statuses[$chcRow->status] ?? ucfirst($chcRow->status) }}</span></td>
                        <td class="chc-actions-cell">
                            <div class="chc-actions">
                                <button type="button" class="chc-btn-sm chc-edit" data-event='@json($chcRow)'>
                                    <i class="fa fa-pencil"></i> {{ __('churchmanagement::lang.edit') }}</button>
                                <form method="post" action="{{ route('churchmanagement.events.destroy', $chcRow->id) }}"
                                      onsubmit="return confirm('Remove this event?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="chc-btn-sm danger">
                                        <i class="fa fa-trash"></i> {{ __('churchmanagement::lang.delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">
                        <div class="empty-state">No events to show.</div>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($installed && $events->hasPages())
            <div style="margin-top:16px">{{ $events->links() }}</div>
        @endif

    </div>
</div>

@endsection

@section('chc_scripts')
<script>
(function () {
    'use strict';

    var form       = document.getElementById('chc-event-form');
    var methodEl   = document.getElementById('chc-form-method');
    var titleEl    = document.getElementById('chc-form-title');
    var submitEl   = document.getElementById('chc-submit-label');
    var cancelEl   = document.getElementById('chc-cancel-edit');
    var storeUrl   = @json(route('churchmanagement.events.store'));
    var updateBase = @json(url(config('churchmanagement.route_prefix', 'church-management') . '/events'));

    if (!form) { return; }

    var fields = ['title','event_type','status','event_date','end_date','venue',
                  'start_time','end_time','organiser_member_id','business_location_id','description'];

    function setValues(row) {
        fields.forEach(function (name) {
            var el = document.getElementById('chc-' + name);
            if (!el) { return; }
            var value = row ? row[name] : '';
            el.value = (value === null || typeof value === 'undefined') ? '' : value;
        });
    }

    document.querySelectorAll('.chc-edit').forEach(function (button) {
        button.addEventListener('click', function () {
            var row;
            try { row = JSON.parse(button.getAttribute('data-event')); } catch (e) { return; }

            setValues(row);
            form.action = updateBase + '/' + row.id;
            methodEl.value = 'PUT';
            titleEl.textContent = 'Edit Event — ' + (row.title || '');
            submitEl.textContent = @json(__('churchmanagement::lang.update'));
            cancelEl.style.display = '';

            document.getElementById('chc-event-form-card').scrollIntoView({behavior:'smooth', block:'start'});
            document.getElementById('chc-title').focus();
        });
    });

    cancelEl.addEventListener('click', function () {
        setValues(null);
        form.action = storeUrl;
        methodEl.value = 'POST';
        titleEl.textContent = 'Add an Event';
        submitEl.textContent = @json(__('churchmanagement::lang.save'));
        cancelEl.style.display = 'none';
        document.getElementById('chc-event_date').value = @json(now()->format('Y-m-d'));
        document.getElementById('chc-status').value = 'planned';
    });
})();
</script>
@endsection
