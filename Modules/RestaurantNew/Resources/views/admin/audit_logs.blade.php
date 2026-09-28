@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::messages.audit_logs'))
@section('content')
<div class="rn-page">
 <div class="rn-toolbar"><h3>{{ __('restaurantnew::messages.audit_logs') }}</h3></div>
 <div class="rn-card table-responsive"><table class="table table-bordered rn-datatable"><thead><tr><th>Date</th><th>User</th><th>Area</th><th>Action</th><th>Entity</th><th>IP</th></tr></thead><tbody>
 @foreach($logs as $log)<tr><td>{{ $log->created_at }}</td><td>{{ $log->user_id }}</td><td>{{ $log->module_area }}</td><td>{{ $log->action }}</td><td>{{ $log->entity_type }} #{{ $log->entity_id }}</td><td>{{ $log->ip_address }}</td></tr>@endforeach
 </tbody></table></div>
</div>
@endsection
