@extends('customers::portal.layout')
@section('title', 'Dealer Messages')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary')
    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Messages</h3></div>
        <div class="dd-card-body">
            @forelse($rows as $row)
                <div class="dd-list-item">
                    <div class="pull-right"><span class="dd-badge {{ $row->status == 'Unread' ? 'dd-badge-unread' : 'dd-badge-read' }}">{{ $row->status }}</span></div>
                    <h4 class="dd-list-title">{{ $row->subject }}</h4>
                    <div class="dd-list-meta">{{ $row->date }} · From: {{ $row->from }}</div>
                    <div>{!! nl2br(e($row->message)) !!}</div>
                    <div class="clearfix"></div>
                </div>
            @empty
                <div class="dd-empty">No messages available.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
