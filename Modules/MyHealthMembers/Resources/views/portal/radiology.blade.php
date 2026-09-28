@extends('myhealthmembers::portal.layout')
@section('title', 'Radiology Reports')
@section('content')
<div class="mh-card"><div class="mh-card-header">Radiology Reports</div><div class="mh-card-body">
@include('myhealthmembers::portal.partials.simple_list', ['rows' => $radiology_reports ?? collect(), 'empty' => 'No radiology reports found.'])
@if(isset($radiology_reports) && method_exists($radiology_reports, 'links')) <div class="text-center">{!! $radiology_reports->links() !!}</div> @endif
</div></div>
@endsection
