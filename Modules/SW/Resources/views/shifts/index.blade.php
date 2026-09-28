@extends('sw::layouts.app', [
    'title' => 'SW Shifts',
    'heading' => 'SW Shifts',
    'subheading' => 'A shift belongs to one location and one date, and has many operators. It must be closed before it can be settled.',
])

@section('sw_content')

<div class="sw-card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
        <form method="get" style="margin:0">
            <div style="display:flex;gap:10px;align-items:end">
                <div class="sw-field" style="margin:0;min-width:220px">
                    <label>Business Location</label>
                    <select name="location_id" onchange="this.form.submit()">
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" @selected((int) $locationId === (int) $loc->id)>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        <a href="{{ route('sw.shifts.create', ['location_id' => $locationId]) }}" class="sw-btn">
            <i class="fa fa-plus"></i> New SW Shift</a>
    </div>

    <table class="sw-table">
        <thead>
            <tr>
                <th>SW Shift No</th><th>Date</th><th>Name</th><th>Times</th>
                <th class="num">Operators</th><th>Status</th><th style="width:1%"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($shifts as $s)
            <tr>
                <td><strong>{{ $s->sw_shift_no }}</strong></td>
                <td>{{ $s->shift_date->format('d/m/Y') }}</td>
                <td>{{ $s->shift_name ?: '—' }}</td>
                <td class="sw-note">
                    {{ $s->start_time ? substr($s->start_time, 0, 5) : '—' }}
                    to {{ $s->end_time ? substr($s->end_time, 0, 5) : '—' }}
                </td>
                <td class="num">{{ $s->operators_count }}</td>
                <td><span class="sw-badge {{ strtolower($s->statusLabel()) }}">{{ $s->statusLabel() }}</span></td>
                <td>
                    @if($s->isOpen())
                        <a class="sw-btn secondary" href="{{ route('sw.shifts.edit', $s->id) }}">
                            <i class="fa fa-pencil"></i> Edit</a>
                    @else
                        <span class="sw-note">Locked</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="sw-empty">
                No SW Shifts at this location yet.
                <a href="{{ route('sw.shifts.create', ['location_id' => $locationId]) }}">Create the first one</a>.
            </td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $shifts->withQueryString()->links() }}
</div>

@endsection
