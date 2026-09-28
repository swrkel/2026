@extends('autoservice::layouts.master')
@section('title','Vehicle Timeline - '.$vehicle->registration_no)
@section('autoservice_content')
<div class="box"><div class="box-body"><h3>{{ $vehicle->registration_no }} - {{ $vehicle->make }} {{ $vehicle->model }}</h3></div></div>
<div class="box"><div class="box-body"><ul class="timeline">@foreach($events as $e)<li><i class="fa fa-car bg-blue"></i><div class="timeline-item"><span class="time">{{ $e->event_at }}</span><h3 class="timeline-header">{{ $e->title }}</h3><div class="timeline-body">{{ $e->description }}</div></div></li>@endforeach</ul>{{ $events->links() }}</div></div>
@endsection
