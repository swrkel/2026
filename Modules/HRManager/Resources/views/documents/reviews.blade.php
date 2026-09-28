@extends('hrmanager::layouts.master')
@section('content')
<div class="hr-page">
    <div class="hr-hero"><div><span class="hr-eyebrow">Performance</span><h1>Performance Reviews</h1><p>Review history, ratings, strengths, goals and recommendations.</p></div><div class="hr-actions"><a href="{{ route('hr.documents.dashboard') }}" class="hr-btn hr-btn-light">Back</a></div></div>
    <div class="hr-card"><table class="hr-table"><thead><tr><th>No</th><th>Employee</th><th>Date</th><th>Rating</th><th>Status</th></tr></thead><tbody>
        @forelse($reviews as $row)<tr><td>{{ $row->review_no }}</td><td>{{ $row->employee_id }}</td><td>{{ $row->review_date }}</td><td>{{ $row->overall_rating }}</td><td><span class="hr-badge">{{ $row->status }}</span></td></tr>
        @empty<tr><td colspan="5" class="hr-empty">No reviews found.</td></tr>@endforelse
    </tbody></table>{{ $reviews->links() }}</div>
</div>
@endsection
