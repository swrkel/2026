@extends('membershipnew::layouts.app')
@php
    $title = 'Dividend Batches';
@endphp
@section('page-actions')<a class="mn-btn mn-btn-purple" href="{{ route('membership-new.dividends.create') }}">Create Dividend Batch</a>@endsection
@section('membership-content')
<div class="mn-panel"><table class="mn-table"><thead><tr><th>Date &amp; Time</th><th>Total Amount</th><th>Per Share</th><th>Members</th><th>Status</th><th>Added By</th><th>Action</th></tr></thead><tbody>@foreach($records as $record)<tr><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($record->dividend_date, $record->created_at) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->total_dividend_amount) }}</td><td class="mn-money">{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($record->dividend_per_share) }}</td><td>{{ $record->payments_count }}</td><td>{{ $record->is_posted ? 'Posted' : 'Draft' }}</td><td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($record) }}</td><td><a href="{{ route('membership-new.dividends.show', $record->id) }}">View</a></td></tr>@endforeach</tbody></table>{{ $records->links() }}</div>
@endsection
