@extends('customers::portal.layout')
@section('title', 'Dealer Announcements')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary')
    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Announcements</h3></div>
        <div class="dd-card-body">
            @forelse($rows as $row)
                <div class="dd-list-item">
                    <h4 class="dd-list-title">{{ $row->title }}</h4>
                    <div class="dd-list-meta">{{ $row->date }}</div>
                    <div>{!! nl2br(e($row->message)) !!}</div>
                    @if(!empty($row->attachment))
                        <div style="margin-top:10px;"><a class="dd-btn dd-btn-default" href="{{ url($row->attachment) }}" target="_blank">Download Attachment</a></div>
                    @endif
                </div>
            @empty
                <div class="dd-empty">No announcements available.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
