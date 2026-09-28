@extends('myhealthmembers::portal.layout')
@section('title', 'Billing')
@section('content')
<div class="mh-card"><div class="mh-card-header">Billing</div><div class="mh-card-body">
@include('myhealthmembers::portal.partials.simple_list', ['rows' => $invoices ?? collect(), 'empty' => 'No billing found.'])
@if(isset($invoices) && method_exists($invoices, 'links')) <div class="text-center">{!! $invoices->links() !!}</div> @endif
</div></div>
@endsection
