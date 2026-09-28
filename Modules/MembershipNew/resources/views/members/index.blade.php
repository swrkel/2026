@extends('membershipnew::layouts.app')
@php
    $title = 'Membership-New Members';
@endphp
@section('page-actions')
    <a class="mn-btn mn-btn-primary" href="{{ route('membership-new.members.create') }}"><i class="fa fa-plus"></i> Add Member</a>
@endsection
@section('membership-content')
<div class="mn-panel mn-members-list-panel">
    <form class="mn-toolbar" method="GET">
        <input name="search" value="{{ request('search') }}" placeholder="Search name, region, mobile, business, type, NIC or status">
    </form>

    <div class="mn-members-table-wrap">
        <table class="mn-table mn-members-table">
            <thead>
                <tr>
                    <th class="mn-members-action-col">Action</th>
                    <th><span class="mn-th-primary">Name</span><span class="mn-th-secondary">Gender</span></th>
                    <th>Region</th>
                    <th>Mobile Number</th>
                    <th>Business Name</th>
                    <th><span class="mn-th-primary">Membership Type</span><span class="mn-th-secondary">Shares</span></th>
                    <th>NIC</th>
                    <th><span class="mn-th-primary">Joined</span><span class="mn-th-secondary">Date &amp; Time</span></th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Added By</th>
                </tr>
            </thead>
            <tbody>
            @forelse($records as $member)
                @php
                    $name = trim((string) ($member->full_name ?: trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''))));
                    $titlePrefix = trim((string) $member->title);
                    if ($titlePrefix !== '' && $name !== '' && stripos($name, $titlePrefix . ' ') !== 0) {
                        $name = $titlePrefix . ' ' . $name;
                    } elseif ($titlePrefix !== '' && $name === '') {
                        $name = $titlePrefix;
                    }

                    $regionText = optional($member->region)->region;
                    $regionNo = optional($member->region)->region_no;
                    $businessName = trim((string) ($member->business_name ?: ($currentBusinessName ?? '')));
                    $membershipType = trim((string) optional($member->membershipType)->setting_value);
                    // No of Shares is entered directly on the member Add/Edit form.
                    // Use that saved value on this list and avoid a latestOfMany join.
                    $shares = $member->no_of_shares ?? 0;
                @endphp
                <tr>
                    <td class="mn-members-action-col">
                        <details class="mn-row-action">
                            <summary class="mn-btn mn-btn-primary mn-btn-sm"><i class="fa fa-bars"></i> Action <i class="fa fa-caret-down"></i></summary>
                            <div class="mn-row-action-menu">
                                <a class="mn-row-action-item mn-row-view" href="{{ route('membership-new.members.show', $member->id) }}"><i class="fa fa-eye"></i> View</a>
                                <a class="mn-row-action-item mn-row-edit" href="{{ route('membership-new.members.edit', $member->id) }}"><i class="fa fa-pencil"></i> Edit</a>
                                <a class="mn-row-action-item mn-row-points" href="{{ route('membership-new.points.member-ledger', $member->id) }}"><i class="fa fa-star"></i> Points</a>
                                <a class="mn-row-action-item mn-row-card" href="{{ route('membership-new.cards.print', $member->id) }}"><i class="fa fa-id-card"></i> Card</a>
                            </div>
                        </details>
                    </td>
                    <td class="mn-member-name-cell">
                        <span class="mn-cell-primary">{{ $name !== '' ? $name : '-' }}</span>
                        <span class="mn-cell-secondary"><strong>Gender:</strong> {{ $member->gender ?: '-' }}</span>
                    </td>
                    <td>
                        <span class="mn-cell-primary">{{ $regionText ?: '-' }}</span>
                        @if($regionNo)
                            <span class="mn-cell-secondary">{{ $regionNo }}</span>
                        @endif
                    </td>
                    <td>{{ $member->mobile ?: '-' }}</td>
                    <td>{{ $businessName !== '' ? $businessName : '-' }}</td>
                    <td>
                        <span class="mn-cell-primary">{{ $membershipType !== '' ? $membershipType : '-' }}</span>
                        <span class="mn-cell-secondary"><strong>Shares:</strong> {{ number_format((float) $shares, 4) }}</span>
                    </td>
                    <td>{{ $member->nic ?: '-' }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($member->joined_on, $member->created_at) }}</td>
                    <td class="mn-member-address-cell">{{ $member->address ?: '-' }}</td>
                    <td><span class="mn-status {{ $member->is_active ? '' : 'inactive' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($member) }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="mn-report-empty">No members found</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mn-members-pagination">{{ $records->links() }}</div>
</div>

<style>
.mn-members-list-panel{padding-bottom:16px}
.mn-members-table-wrap{width:100%;overflow-x:auto;overflow-y:visible;border:1px solid #e6edf5;border-radius:14px;background:#fff;-webkit-overflow-scrolling:touch}
.mn-members-table{min-width:1280px}
.mn-members-table thead th{vertical-align:middle;text-align:left}
.mn-members-table tbody td{vertical-align:top}
.mn-members-action-col{width:126px;min-width:126px}
.mn-th-primary,.mn-th-secondary,.mn-cell-primary,.mn-cell-secondary{display:block}
.mn-th-secondary{margin-top:2px;color:#64748b;font-size:11px;letter-spacing:.02em}
.mn-cell-primary{font-weight:800;color:#172033;line-height:1.35}
.mn-cell-secondary{margin-top:4px;color:#64748b;font-size:12px;line-height:1.35}
.mn-member-name-cell{min-width:190px}
.mn-member-address-cell{min-width:210px;max-width:300px;white-space:normal;line-height:1.45;word-break:break-word}
.mn-members-pagination{display:flex;justify-content:flex-end;margin-top:12px}
.mn-row-action{display:block;min-width:105px}
.mn-row-action>summary{list-style:none;user-select:none}
.mn-row-action>summary::-webkit-details-marker{display:none}
.mn-row-action[open]>summary{background:#fff!important;color:#0f172a!important;border-color:#2563eb!important;box-shadow:inset 0 0 0 1px #dbeafe!important}
.mn-row-action-menu{display:grid;gap:6px;width:138px;margin-top:7px;padding:7px;background:#fff;border:1px solid #dbe7f3;border-radius:11px;box-shadow:0 10px 24px rgba(15,23,42,.10)}
.mn-row-action-item{display:flex!important;align-items:center;gap:7px;min-height:32px;padding:7px 9px;border-radius:8px;color:#fff!important;text-decoration:none!important;font-size:12px;font-weight:800!important;white-space:nowrap}
.mn-row-action-item:hover{filter:brightness(.94);color:#fff!important;text-decoration:none!important}
.mn-row-view{background:#0891b2}.mn-row-edit{background:#f59e0b}.mn-row-points{background:#7c3aed}.mn-row-card{background:#16a34a}
@media(max-width:700px){.mn-members-list-panel{padding:12px}.mn-members-table{min-width:1180px}.mn-members-table thead th,.mn-members-table tbody td{padding:9px 10px}.mn-toolbar input{max-width:none;width:100%}}
</style>
@endsection
