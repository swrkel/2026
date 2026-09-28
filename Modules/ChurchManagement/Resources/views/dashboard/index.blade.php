@extends('churchmanagement::layouts.app', [
    'title' => __('churchmanagement::lang.church_management'),
    'heading' => __('churchmanagement::lang.church_management'),
    'subheading' => 'Members, families and congregation records for this business.',
])

@section('chc_content')

@if(! $installed)
    @include('churchmanagement::partials.install_notice')
@endif

@php
    /*
     | Each tile links to the list that produced it, so a number here is a
     | starting point rather than a dead end. The status filter is carried in
     | the query string, so the list opens already narrowed to what was clicked.
     */
    $chcTiles = [
        [
            'label' => 'Members',
            'value' => $counts['members'],
            'icon'  => 'fa fa-users',
            'tone'  => '',
            'hint'  => 'Active congregation',
            'url'   => route('churchmanagement.members.index', ['status' => 'member']),
        ],
        [
            'label' => 'Families',
            'value' => $counts['families'],
            'icon'  => 'fa fa-home',
            'tone'  => 'success',
            'hint'  => 'Households on the roll',
            'url'   => route('churchmanagement.families.index'),
        ],
        [
            'label' => 'Visitors',
            'value' => $counts['visitors'],
            'icon'  => 'fa fa-user-plus',
            'tone'  => 'warning',
            'hint'  => 'Attending, not yet members',
            'url'   => route('churchmanagement.members.index', ['status' => 'visitor']),
        ],
        [
            'label' => 'Given This Month',
            'value' => null,   // money, formatted below rather than counted
            'money' => $donationsThisMonth,
            'icon'  => 'fa fa-gift',
            'tone'  => 'cyan',
            'hint'  => now()->format('F Y'),
            'url'   => route('churchmanagement.donations.index'),
        ],
        [
            'label' => 'Upcoming Events',
            'value' => $counts['events'],
            'icon'  => 'fa fa-calendar',
            'tone'  => 'purple',
            'hint'  => 'Planned or confirmed',
            'url'   => route('churchmanagement.events.index'),
        ],
        [
            'label' => 'Inactive',
            'value' => $counts['inactive'],
            'icon'  => 'fa fa-user-times',
            'tone'  => 'purple',
            'hint'  => 'On the roll, not attending',
            'url'   => route('churchmanagement.members.index', ['status' => 'inactive']),
        ],
    ];
@endphp

{{-- Six tiles, so three per row on a wide screen rather than four then two. --}}
<div class="ch-kpi-grid ch-standard-grid" style="grid-template-columns:repeat(3,minmax(180px,1fr))">
    @foreach($chcTiles as $chcTile)
        <a href="{{ $chcTile['url'] }}" class="ch-kpi-link">
            <div class="ch-kpi {{ $chcTile['tone'] }}">
                <div class="ch-kpi-top">
                    <div class="ch-icon"><i class="{{ $chcTile['icon'] }}"></i></div>
                    <div class="label-text">{{ $chcTile['label'] }}</div>
                </div>
                {{-- Money tiles carry their own precision; counts are whole numbers. --}}
                <div class="value">
                    @if(array_key_exists('money', $chcTile))
                        {{ number_format((float) $chcTile['money'], (int) (is_numeric(session('business.currency_precision')) ? session('business.currency_precision') : 2)) }}
                    @else
                        {{ number_format($chcTile['value']) }}
                    @endif
                </div>
                <div class="hint">{{ $chcTile['hint'] }}
                    <span class="ch-drill">Open <i class="fa fa-angle-right"></i></span></div>
                <div class="spark"></div>
            </div>
        </a>
    @endforeach
</div>

<div class="ch-two-col">

    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-users text-primary"></i> Recently Added Members</h3>
                <div class="ch-card-subtitle">The last eight people added to the roll.</div>
            </div>
            <a href="{{ route('churchmanagement.members.index') }}" class="btn btn-default btn-sm">
                <i class="fa fa-list"></i> View all</a>
        </div>
        <div class="ch-card-body">
            <div class="table-responsive">
                <table class="chc-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentMembers as $chcMember)
                        <tr>
                            <td>{{ $chcMember->member_code }}</td>
                            <td>{{ $chcMember->full_name }}</td>
                            <td>{{ $chcMember->phone }}</td>
                            <td><span class="chc-pill {{ $chcMember->membership_status }}">
                                {{ ucfirst($chcMember->membership_status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4">
                            <div class="empty-state">No members yet. Add the first from the Members page.</div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="ch-card">
        <div class="ch-card-header">
            <div>
                <h3 class="ch-card-title"><i class="fa fa-birthday-cake text-primary"></i> Birthdays This Month</h3>
                <div class="ch-card-subtitle">{{ now()->format('F') }}, by day.</div>
            </div>
        </div>
        <div class="ch-card-body">
            <div class="table-responsive">
                <table class="chc-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Name</th>
                            <th>Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($birthdays as $chcBirthday)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($chcBirthday->date_of_birth)->format('d M') }}</td>
                            <td>{{ $chcBirthday->full_name }}</td>
                            <td>{{ $chcBirthday->phone }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">
                            <div class="empty-state">No birthdays recorded for this month.</div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="ch-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-calendar text-primary"></i> Upcoming Events</h3>
            <div class="ch-card-subtitle">Planned and confirmed, soonest first.</div>
        </div>
        <a href="{{ route('churchmanagement.events.index') }}" class="btn btn-default btn-sm">
            <i class="fa fa-list"></i> View all</a>
    </div>
    <div class="ch-card-body">
        <div class="table-responsive">
            <table class="chc-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event</th>
                        <th>Venue</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($upcomingEvents as $chcEvent)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($chcEvent->event_date)->format('d M Y') }}</td>
                        <td>{{ $chcEvent->title }}</td>
                        <td>{{ $chcEvent->venue ?: '—' }}</td>
                        <td><span class="chc-pill {{ $chcEvent->status === 'confirmed' ? 'visitor' : 'inactive' }}">
                            {{ ucfirst($chcEvent->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4">
                        <div class="empty-state">Nothing scheduled. Add an event from the Events page.</div>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
