@extends('layouts.app')
@section('title', $title ?? 'Live Operations Board')
@section('content')
<div class="expnew-page">
    <div class="expnew-header"><h1>{{ $title ?? 'Live Operations Board' }}</h1></div>
    <div class="expnew-timeline" id="expnew-operations-feed">
        @forelse(($events ?? []) as $event)
            <div class="expnew-timeline-row">
                <strong>{{ $event->event_title ?? 'Expense Event' }}</strong>
                <span>{{ $event->event_time ?? '' }}</span>
                <p>{{ $event->event_message ?? '' }}</p>
            </div>
        @empty
            <div class="expnew-empty">No live events found.</div>
        @endforelse
    </div>
</div>
@endsection
