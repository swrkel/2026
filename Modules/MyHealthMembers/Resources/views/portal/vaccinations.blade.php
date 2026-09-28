@extends('myhealthmembers::portal.layout')
@section('title', 'Vaccinations')
@section('content')
<div class="mh-card"><div class="mh-card-header">Vaccinations</div><div class="mh-card-body">
@include('myhealthmembers::portal.partials.simple_list', ['rows' => $vaccinations ?? collect(), 'empty' => 'No vaccinations found.'])
@if(isset($vaccinations) && method_exists($vaccinations, 'links')) <div class="text-center">{!! $vaccinations->links() !!}</div> @endif
</div></div>
@endsection
