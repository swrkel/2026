@extends('churchmanagement::layouts.app', [
    'title' => __('churchmanagement::lang.attendance'),
    'heading' => __('churchmanagement::lang.attendance'),
    'subheading' => 'Services, and the register taken against them. Record a headcount, a register, or both.',
])

@section('chc_content')

@if(! $installed)
    @include('churchmanagement::partials.install_notice')
@endif

<div class="ch-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-plus text-primary"></i> Add a Service</h3>
            <div class="ch-card-subtitle">
                A headcount alone is enough if names are not taken. Saving opens the register.
            </div>
        </div>
    </div>
    <div class="ch-card-body">
        <form method="post" action="{{ route('churchmanagement.attendance.services.store') }}">
            @csrf
            <div class="chc-form-grid">
                <div class="chc-field">
                    <label>Title <span style="color:#dc2626">*</span></label>
                    <input type="text" name="title" maxlength="191" required
                           value="{{ old('title') }}" placeholder="Sunday Morning Service">
                </div>
                <div class="chc-field">
                    <label>Date <span style="color:#dc2626">*</span></label>
                    <input type="date" name="service_date" required
                           value="{{ old('service_date', now()->format('Y-m-d')) }}">
                </div>
                <div class="chc-field">
                    <label>Time</label>
                    <input type="time" name="service_time" value="{{ old('service_time') }}">
                </div>

                <div class="chc-field">
                    <label>Type</label>
                    <input type="text" name="service_type" maxlength="50"
                           value="{{ old('service_type') }}" placeholder="Sunday, Midweek, Special…">
                </div>
                <div class="chc-field">
                    <label>Headcount</label>
                    <input type="number" min="0" name="headcount" value="{{ old('headcount') }}">
                    <div class="chc-hint">Leave blank if the room was not counted.</div>
                </div>
                @if($locations->count())
                    <div class="chc-field">
                        <label>Location</label>
                        <select name="business_location_id">
                            <option value="">— Not set —</option>
                            @foreach($locations as $chcLocId => $chcLocName)
                                <option value="{{ $chcLocId }}">{{ $chcLocName }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="chc-field" style="grid-column:1 / -1">
                    <label>{{ __('churchmanagement::lang.notes') }}</label>
                    <textarea name="notes" maxlength="2000">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="chc-form-actions">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Add Service</button>
            </div>
        </form>
    </div>
</div>

@if($selected)
    {{--
        The register for one service.

        Rendered above the service list when a service is open, because it is
        what the user came to do; the list is context.
    --}}
    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-check-square-o text-primary"></i>
                    Register — {{ $selected->title }}</h3>
                <div class="ch-card-subtitle">
                    {{ \Carbon\Carbon::parse($selected->service_date)->format('d M Y') }}
                    @if($selected->service_time) at {{ \Carbon\Carbon::parse($selected->service_time)->format('H:i') }} @endif
                    · Present {{ $summary['present'] }}
                    · Absent {{ $summary['absent'] }}
                    · Excused {{ $summary['excused'] }}
                    · Unmarked {{ $summary['unmarked'] }}
                </div>
            </div>
            <a href="{{ route('churchmanagement.attendance.index') }}" class="btn btn-default btn-sm">
                <i class="fa fa-times"></i> Close register</a>
        </div>
        <div class="ch-card-body">
            @if($members->isEmpty())
                <div class="empty-state">
                    No active members to mark. Add members first, or record a headcount on the service.
                </div>
            @else
                <form method="post" action="{{ route('churchmanagement.attendance.register.save', $selected->id) }}">
                    @csrf
                    <div class="ch-toolbar">
                        <div style="font-weight:700;color:#334155">
                            {{ $members->count() }} member(s)
                        </div>
                        <div style="display:flex;gap:8px">
                            {{-- Marks every row at once; nothing is saved until Save Register. --}}
                            <button type="button" class="btn btn-default btn-sm chc-mark-all" data-status="present">
                                <i class="fa fa-check"></i> All present</button>
                            <button type="button" class="btn btn-default btn-sm chc-mark-all" data-status="">
                                <i class="fa fa-eraser"></i> Clear all</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="chc-table">
                            <thead>
                                <tr>
                                    <th>{{ __('churchmanagement::lang.member_code') }}</th>
                                    <th>{{ __('churchmanagement::lang.full_name') }}</th>
                                    <th style="width:280px">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($members as $chcMember)
                                @php $chcMark = $register[$chcMember->id] ?? ''; @endphp
                                <tr>
                                    <td>{{ $chcMember->member_code }}</td>
                                    <td>{{ $chcMember->full_name }}</td>
                                    <td>
                                        <select name="attendance[{{ $chcMember->id }}]" class="chc-mark">
                                            {{-- Blank means "not recorded", which is not the
                                                 same as absent and is stored as no row. --}}
                                            <option value="" @selected($chcMark === '')>— Not recorded —</option>
                                            @foreach($statuses as $chcKey => $chcLabel)
                                                <option value="{{ $chcKey }}" @selected($chcMark === $chcKey)>{{ $chcLabel }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="chc-form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Save Register</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endif

<div class="ch-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-list text-primary"></i> Services</h3>
            <div class="ch-card-subtitle">{{ $installed ? $services->total() : 0 }} service(s) in this period.</div>
        </div>
    </div>
    <div class="ch-card-body">

        <form method="get" class="ch-toolbar">
            <div class="chc-filters" style="flex:1">
                <div class="chc-field">
                    <label>From</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="chc-field">
                    <label>To</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="chc-field" style="flex:0 0 auto;min-width:0">
                    <label>&nbsp;</label>
                    <div style="display:flex;gap:8px">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Apply</button>
                        <a href="{{ route('churchmanagement.attendance.index') }}" class="btn btn-default">
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
                        <th>Service</th>
                        <th>Type</th>
                        <th>Headcount</th>
                        <th>Marked Present</th>
                        <th class="chc-actions-cell">{{ __('churchmanagement::lang.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($services as $chcRow)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($chcRow->service_date)->format('d M Y') }}</td>
                        <td>
                            <strong>{{ $chcRow->title }}</strong>
                            @if($chcRow->service_time)
                                <div class="ch-page-note">{{ \Carbon\Carbon::parse($chcRow->service_time)->format('H:i') }}</div>
                            @endif
                        </td>
                        <td>{{ $chcRow->service_type ?: '—' }}</td>
                        <td>{{ $chcRow->headcount === null ? '—' : number_format($chcRow->headcount) }}</td>
                        <td><span class="ch-badge-soft info">{{ $chcRow->present_count ?? 0 }}</span></td>
                        <td class="chc-actions-cell">
                            <div class="chc-actions">
                                <a class="chc-btn-sm"
                                   href="{{ route('churchmanagement.attendance.index', array_merge($filters, ['service_id' => $chcRow->id])) }}">
                                    <i class="fa fa-check-square-o"></i> Register</a>
                                <form method="post" action="{{ route('churchmanagement.attendance.services.destroy', $chcRow->id) }}"
                                      onsubmit="return confirm('Remove this service and its register?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="chc-btn-sm danger">
                                        <i class="fa fa-trash"></i> {{ __('churchmanagement::lang.delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">
                        <div class="empty-state">No services in this period.</div>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($installed && $services->hasPages())
            <div style="margin-top:16px">{{ $services->links() }}</div>
        @endif

    </div>
</div>

@endsection

@section('chc_scripts')
<script>
(function () {
    'use strict';

    /* Marks every row at once. Nothing is written until Save Register, so this
       is a convenience rather than an action. */
    document.querySelectorAll('.chc-mark-all').forEach(function (button) {
        button.addEventListener('click', function () {
            var status = button.getAttribute('data-status') || '';
            document.querySelectorAll('select.chc-mark').forEach(function (select) {
                select.value = status;
            });
        });
    });
})();
</script>
@endsection
