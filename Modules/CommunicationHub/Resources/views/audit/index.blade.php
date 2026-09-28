@extends('layouts.app')
@section('title', $title ?? 'Communication Hub')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'Communication Hub' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

@php($title='Communication Hub Standalone Audit')
<div class="box box-success"><div class="box-header with-border"><h3 class="box-title">Standalone Checklist</h3></div><div class="box-body table-responsive"><table class="table table-bordered"><thead><tr><th>Area</th><th>Status</th><th>Note</th></tr></thead><tbody>@foreach($checklist as $item)<tr><td>{{ $item['area'] }}</td><td><span class="label label-success">{{ strtoupper($item['status']) }}</span></td><td>{{ $item['note'] }}</td></tr>@endforeach</tbody></table></div></div>
<div class="box"><div class="box-header"><h3 class="box-title">Recent Audit Logs</h3></div><div class="box-body table-responsive"><table class="table table-bordered"><thead><tr><th>Action</th><th>User</th><th>IP</th><th>Date</th></tr></thead><tbody>@foreach($logs as $log)<tr><td>{{ $log->action }}</td><td>{{ $log->user_id }}</td><td>{{ $log->ip_address }}</td><td>{{ $log->created_at }}</td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection
