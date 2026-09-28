@extends('myhealthmembers::portal.layout')
@section('title', 'Notifications')
@section('content')
<div class="mh-card"><div class="mh-card-header">Notifications</div><div class="mh-card-body">
@include('myhealthmembers::portal.partials.simple_list', ['rows' => $notifications ?? collect(), 'empty' => 'No notifications found.'])
@if(isset($notifications) && method_exists($notifications, 'links')) <div class="text-center">{!! $notifications->links() !!}</div> @endif
</div></div>
@endsection
