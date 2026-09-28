@extends('myhealthmembers::portal.layout')
@section('title', 'Medical History')
@section('content')
<div class="mh-card"><div class="mh-card-header">Medical History</div><div class="mh-card-body">
@include('myhealthmembers::portal.partials.simple_list', ['rows' => $history ?? collect(), 'empty' => 'No medical history found.'])
@if(isset($history) && method_exists($history, 'links')) <div class="text-center">{!! $history->links() !!}</div> @endif
</div></div>
@endsection
