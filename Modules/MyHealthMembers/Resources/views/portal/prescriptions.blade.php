@extends('myhealthmembers::portal.layout')
@section('title', 'Prescriptions')
@section('content')
<div class="mh-card"><div class="mh-card-header">Prescriptions</div><div class="mh-card-body">
@include('myhealthmembers::portal.partials.simple_list', ['rows' => $prescriptions ?? collect(), 'empty' => 'No prescriptions found.'])
@if(isset($prescriptions) && method_exists($prescriptions, 'links')) <div class="text-center">{!! $prescriptions->links() !!}</div> @endif
</div></div>
@endsection
