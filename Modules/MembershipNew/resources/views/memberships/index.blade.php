@extends('membershipnew::layouts.app')
@php
    $title = 'Plans';
@endphp
@section('page-actions')
<a class="mn-btn mn-btn-primary" href="{{ route('membership-new.plans.create') }}">Add New</a>
@endsection
@section('membership-content')
<div class="mn-panel">
    <table class="mn-table">
        <thead><tr><th>ID</th><th>Name / Reference</th><th>Status / Amount</th><th>Date &amp; Time</th><th>Added By</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($records as $record)
            <tr>
                <td>{{ $record->id }}</td>
                <td>{{ $record->first_name ?? $record->name ?? $record->payment_ref_no ?? '-' }}</td>
                <td class="mn-money">{{ $record->amount !== null ? \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->amount) : ($record->is_active ? 'Active' : 'Inactive') }}</td>
                <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->created_at) }}</td>
                <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td>
                <td><a href="#">View</a></td>
            </tr>
        @empty
            <tr><td colspan="6">No records found</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $records->links() }}
</div>
@endsection
