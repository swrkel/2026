@extends('managementreport::layouts.master', [
    'pageTitle' => $run->report_title,
    'pageSubtitle' => 'Saved snapshot: '.$run->uuid
])

@section('page_actions')
<a href="{{ route('managementreport.dashboard') }}" class="mgmt-btn mgmt-btn-default"><i class="fa fa-dashboard"></i> Dashboard</a>
<a target="_blank" href="{{ route('managementreport.daily.print', $run) }}" class="mgmt-btn mgmt-btn-info"><i class="fa fa-print"></i> Print</a>
<a href="{{ route('managementreport.daily.pdf', $run) }}" class="mgmt-btn mgmt-btn-danger"><i class="fa fa-file-pdf-o"></i> PDF</a>
<button type="button" class="mgmt-btn mgmt-btn-success" data-mgmt-open-share><i class="fa fa-paper-plane"></i> Share</button>
@endsection

@section('managementreport_content')
<div class="mgmt-report-paper mgmt-saved-paper">
    @include('managementreport::daily.partials.report-body', ['report' => $report])
</div>

<div class="mgmt-panel mgmt-review-panel">
    <div class="mgmt-panel-header"><h3><i class="fa fa-check-circle-o"></i> Management Review</h3><span class="mgmt-status mgmt-status-{{ $run->review_status }}">{{ ucfirst($run->review_status) }}</span></div>
    <form method="POST" action="{{ route('managementreport.reviews.store', $run) }}" class="mgmt-review-form">
        @csrf
        <div class="mgmt-field"><label>Review Status</label><select name="review_status" required>@foreach(['pending','reviewed','approved','rejected'] as $status)<option value="{{ $status }}" {{ $run->review_status === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="mgmt-field mgmt-field-grow"><label>Review Notes</label><input type="text" name="review_notes" placeholder="Management observation or action required"></div>
        <button class="mgmt-btn mgmt-btn-primary" type="submit">Save Review</button>
    </form>
    @if($run->reviews->count())
        <div class="mgmt-review-history">
            @foreach($run->reviews->sortByDesc('reviewed_at') as $review)
                <div><strong>{{ ucfirst($review->review_status) }}</strong><span>{{ $review->review_notes ?: 'No notes' }}</span><small>{{ optional($review->reviewed_at)->format('d M Y, h:i A') }}</small></div>
            @endforeach
        </div>
    @endif
</div>

@include('managementreport::daily.partials.share-modal', ['run' => $run])
@endsection
