@extends('myhealthmembers::portal.layout')
@section('title', 'Laboratory Results')
@section('content')
<div class="mh-card"><div class="mh-card-header">Laboratory Results</div><div class="mh-card-body">
@include('myhealthmembers::portal.partials.simple_list', ['rows' => $lab_results ?? collect(), 'empty' => 'No laboratory results found.'])
@if(isset($lab_results) && method_exists($lab_results, 'links')) <div class="text-center">{!! $lab_results->links() !!}</div> @endif
</div></div>
@endsection
