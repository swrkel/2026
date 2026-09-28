@extends('layouts.app')
@section('title', __('distributionnew::lang.deployment_logs'))
@section('content')
<section class="content-header"><h1>{{ __('distributionnew::lang.deployment_logs') }}</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
<table class="table table-bordered table-striped" id="disnew_deployment_logs_table"><thead><tr><th>Date</th><th>Stage</th><th>Status</th><th>Note</th></tr></thead><tbody>
@foreach($logs as $log)<tr><td>{{ $log->created_at }}</td><td>{{ $log->stage }}</td><td>{{ $log->status }}</td><td>{{ $log->note }}</td></tr>@endforeach
</tbody></table>
</div></div></section>
@endsection
