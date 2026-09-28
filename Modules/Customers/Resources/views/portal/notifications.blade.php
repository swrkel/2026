@extends('customers::portal.layout')
@section('title', 'Dealer Notifications')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary')
    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Notifications</h3></div>
        <div class="dd-card-body">
            @forelse($rows as $row)
                <div class="dd-list-item">
                    <div class="pull-right">
                        <span class="dd-badge {{ $row->status == 'Unread' ? 'dd-badge-unread' : 'dd-badge-read' }}">{{ $row->status }}</span>
                    </div>
                    <h4 class="dd-list-title">{{ $row->title }}</h4>
                    <div class="dd-list-meta">{{ $row->date }} · {{ $row->type }}</div>
                    <div>{{ $row->message }}</div>
                    <div class="clearfix"></div>
                </div>
            @empty
                <div class="dd-empty">No notifications available.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
